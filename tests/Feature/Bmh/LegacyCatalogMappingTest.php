<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Domain\Catalog\CatalogSemanticLayer;
use App\Domain\Catalog\Contracts\CatalogRepositoryInterface;
use App\Domain\Catalog\DTO\ProductView;
use App\Domain\Catalog\LegacyAttributeMap;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * El anti-corruption layer contra la base legacy real.
 *
 * Estos tests NO usan fixtures: corren contra la copia local de la base de BMH.
 * Es deliberado — lo que se está verificando es justamente que el mapeo
 * sobreviva a los datos como realmente están, con sus NULL, sus "-" y sus
 * códigos repetidos.
 */
final class LegacyCatalogMappingTest extends TestCase
{
    private function catalog(): CatalogRepositoryInterface
    {
        return app(CatalogRepositoryInterface::class);
    }

    public function test_la_conexion_legacy_es_de_solo_lectura(): void
    {
        // La barrera no es una convención de código: es un GRANT de MySQL.
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::connection('mysql_legacy')
            ->table('productos')
            ->where('id', 1)
            ->update(['orden' => 'test']);
    }

    public function test_mapea_los_slots_legacy_a_atributos_con_etiqueta(): void
    {
        $products = $this->catalog()->findByCode('1833');

        $this->assertNotEmpty($products, 'El producto 1833 debería existir en la base legacy.');

        $product = $products[0];

        $this->assertSame('ROTORES', $product->category?->name);

        // columna_1 = VOLTAJE para ROTORES.
        $voltage = $product->attribute('voltage');
        $this->assertNotNull($voltage, 'Debería mapear columna_1 a voltage.');
        $this->assertSame('24v', $voltage->value);
        $this->assertSame('columna_1', $voltage->sourceField);
        $this->assertSame('productos', $voltage->sourceTable);

        // columna_2 = DIAMETRO, con unidad derivada del diccionario.
        $diameter = $product->attribute('diameter');
        $this->assertNotNull($diameter);
        $this->assertSame('125 mm', $diameter->displayValue());
    }

    public function test_no_expone_valores_de_slots_sin_etiqueta(): void
    {
        // columna_3 tiene 362 valores cargados pero ninguna categoría lo
        // etiqueta: es un dato no interpretable y no debe salir del ACL.
        $orphanKeys = array_map(
            static fn (int $slot): string => LegacyAttributeMap::column($slot),
            LegacyAttributeMap::ORPHAN_SLOTS,
        );

        $row = DB::connection('mysql_legacy')->table('productos')
            ->whereNotNull('columna_3')
            ->where('columna_3', '<>', '')
            ->first();

        if ($row === null) {
            $this->markTestSkipped('No hay productos con columna_3 cargada.');
        }

        $product = $this->catalog()->find((int) $row->id);

        $this->assertNotNull($product);

        foreach ($product->attributes as $attribute) {
            $this->assertNotContains(
                $attribute->sourceField,
                $orphanKeys,
                'Un slot sin etiqueta no puede exponerse como atributo.',
            );
        }
    }

    public function test_todo_atributo_de_base_es_factual(): void
    {
        $product = $this->catalog()->findByCode('1833')[0] ?? null;

        $this->assertNotNull($product);

        foreach ($product->attributes as $attribute) {
            $this->assertTrue(
                $attribute->provenance->isFactual(),
                "El atributo {$attribute->key} viene de la base y debería ser factual.",
            );
            $this->assertFalse($attribute->provenance->isInference());
        }
    }

    public function test_descarta_los_placeholders_del_eav(): void
    {
        // `producto_caracteristica` usa "-" como "no aplica". Eso no es una
        // pieza relacionada ni una equivalencia.
        $product = $this->catalog()->findByCode('1833')[0] ?? null;

        $this->assertNotNull($product);

        foreach ([...$product->relatedParts, ...$product->equivalences] as $reference) {
            $this->assertNotSame('-', $reference->code);
            $this->assertNotSame('', trim($reference->code));
        }
    }

    public function test_extrae_las_piezas_relacionadas_del_eav_moderno(): void
    {
        $product = $this->catalog()->findByCode('1833')[0] ?? null;

        $this->assertNotNull($product);

        $labels = array_map(static fn ($r): string => $r->label, $product->relatedParts);

        $this->assertContains('COLECTOR', $labels, 'El rotor 1833 tiene un COLECTOR asociado en la base.');
    }

    public function test_normaliza_codigos_para_busqueda_tolerante(): void
    {
        $this->assertSame('REG40016', CatalogSemanticLayer::normalizeCode('reg 40-016'));
        $this->assertSame('REG40016', CatalogSemanticLayer::normalizeCode('REG.40/016'));

        $direct     = $this->catalog()->findByCode('REG40016');
        $normalized = $this->catalog()->findByNormalizedCode('reg 40-016');

        $this->assertNotEmpty($direct);
        $this->assertSame(
            array_map(static fn (ProductView $p): int => $p->id, $direct),
            array_map(static fn (ProductView $p): int => $p->id, $normalized),
        );
    }

    public function test_el_texto_de_busqueda_no_depende_de_descripcion(): void
    {
        // `descripcion` está vacía en 5.035 de 5.054 productos: el texto útil
        // tiene que salir del nombre, los atributos y las equivalencias.
        $product = $this->catalog()->findByCode('1833')[0] ?? null;

        $this->assertNotNull($product);
        $this->assertStringContainsString('CODIGO: 1833', $product->searchableText);
        $this->assertStringContainsString('RUBRO: ROTORES', $product->searchableText);
        $this->assertStringContainsString('CARACTERISTICAS:', $product->searchableText);
    }

    public function test_marca_los_codigos_duplicados(): void
    {
        $duplicates = $this->catalog()->duplicateCodes();

        $this->assertNotEmpty($duplicates, 'La base tiene 11 códigos colisionando.');

        $code     = (string) array_key_first($duplicates);
        $products = $this->catalog()->findByCode($code);

        $this->assertGreaterThan(1, count($products));

        foreach ($products as $product) {
            $this->assertTrue($product->hasDuplicateCode);
        }
    }

    public function test_el_diccionario_de_slots_no_tiene_claves_repetidas(): void
    {
        $keys = array_map(
            static fn (array $definition): string => $definition[0],
            LegacyAttributeMap::all(),
        );

        $this->assertSame(
            count($keys),
            count(array_unique($keys)),
            'Dos slots no pueden compartir la misma clave canónica.',
        );
    }

    public function test_resuelve_sinonimos_del_cliente_a_claves_canonicas(): void
    {
        $this->assertSame('voltage', LegacyAttributeMap::resolveTerm('tensión'));
        $this->assertSame('voltage', LegacyAttributeMap::resolveTerm('VOLTAJE'));
        $this->assertSame('outer_diameter', LegacyAttributeMap::resolveTerm('diametro externo'));
        $this->assertSame('splines', LegacyAttributeMap::resolveTerm('estrías'));
        $this->assertNull(LegacyAttributeMap::resolveTerm('color favorito'));
    }

    public function test_no_hay_n_mas_uno_al_hidratar_muchos_productos(): void
    {
        $ids = DB::connection('mysql_legacy')->table('productos')
            ->limit(40)->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        // Se calientan los caches de categorías y duplicados para medir sólo la
        // hidratación.
        $this->catalog()->findMany([$ids[0]]);

        DB::connection('mysql_legacy')->flushQueryLog();
        DB::connection('mysql_legacy')->enableQueryLog();

        $products = $this->catalog()->findMany($ids);

        $queries = DB::connection('mysql_legacy')->getQueryLog();
        DB::connection('mysql_legacy')->disableQueryLog();

        $this->assertCount(40, $products);
        $this->assertLessThanOrEqual(
            7,
            count($queries),
            'Hidratar 40 productos debe costar un número fijo de queries, no una por producto.',
        );
    }
}
