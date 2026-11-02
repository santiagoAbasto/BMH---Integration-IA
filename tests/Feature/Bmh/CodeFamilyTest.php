<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Domain\Catalog\CatalogFamilyResolver;
use App\Domain\Search\DTO\SearchQuery;
use App\Domain\Search\HybridProductSearchService;
use Tests\TestCase;

/**
 * La convención de códigos de BMH: la familia va adelante.
 *
 * En la pieza está grabado "17705". En el catálogo eso es REG17705 si es un
 * regulador y POL17705 si es una polea. Sin conocer la convención, pedir el
 * regulador 17705 devolvía las dos cosas con la misma confianza baja, y el
 * cliente terminaba dudando de todo el listado.
 */
final class CodeFamilyTest extends TestCase
{
    private function familias(): CatalogFamilyResolver
    {
        return app(CatalogFamilyResolver::class);
    }

    /** @return list<string> códigos de los candidatos, en orden */
    private function buscar(string $texto): array
    {
        $query          = new SearchQuery();
        $query->rawText = $texto;

        return array_map(
            static fn ($c): string => $c->product->code,
            app(HybridProductSearchService::class)->search($query),
        );
    }

    public function test_nombrar_la_pieza_alcanza_para_completar_el_codigo(): void
    {
        $codigos = $this->buscar('necesito el regulador 17705');

        $this->assertContains('REG17705', $codigos);
        $this->assertNotContains('POL17705', $codigos, 'la polea del mismo número no es un regulador');
    }

    public function test_un_codigo_completado_puntua_como_exacto(): void
    {
        $query          = new SearchQuery();
        $query->rawText = 'necesito el regulador 17705';

        $candidatos = app(HybridProductSearchService::class)->search($query);

        $this->assertSame('REG17705', $candidatos[0]->product->code);
        $this->assertSame('very_high', $candidatos[0]->confidenceBand());
    }

    public function test_un_numero_suelto_sigue_siendo_ambiguo(): void
    {
        // Sin decir de qué pieza se trata, "17705" es genuinamente ambiguo.
        // Inventar una familia sería adivinar: se muestran las dos y se pregunta.
        $codigos = $this->buscar('17705');

        $this->assertContains('REG17705', $codigos);
        $this->assertContains('POL17705', $codigos);
    }

    public function test_un_codigo_ya_completo_no_se_toca(): void
    {
        $codigos = $this->buscar('REG17705');

        $this->assertSame('REG17705', $codigos[0]);
    }

    public function test_el_rubro_se_reconoce_aunque_se_lo_nombre_distinto(): void
    {
        $catalog = app(\App\Domain\Catalog\Contracts\CatalogRepositoryInterface::class);

        $nombre = static function (array $ids) use ($catalog): array {
            return array_map(static fn (int $id): string => $catalog->category($id)?->name ?? '', $ids);
        };

        // Singular, plural y frase entera tienen que dar el mismo rubro.
        $this->assertSame(['REGULADOR DE VOLTAJE'], $nombre($this->familias()->rubros(['regulador de voltaje'])));
        $this->assertSame(['REGULADOR DE VOLTAJE'], $nombre($this->familias()->rubros(['reguladores de voltaje'])));
        $this->assertSame(['REGULADOR DE VOLTAJE'], $nombre($this->familias()->rubros(['necesito un regulador de voltaje'])));

        // Y compartir una palabra suelta no alcanza.
        $this->assertSame(['MOTORES DE ARRANQUE'], $nombre($this->familias()->rubros(['motor de arranque'])));
    }

    public function test_lo_que_no_es_un_rubro_no_se_fuerza(): void
    {
        $this->assertSame([], $this->familias()->rubros(['flux capacitor']));
        $this->assertSame([], $this->familias()->rubros(['hola qué tal']));
    }

    public function test_solo_se_completan_numeros_no_codigos_con_letras(): void
    {
        $rubros = $this->familias()->rubros(['regulador de voltaje']);

        $this->assertNotEmpty($this->familias()->completarCodigo('17705', $rubros));

        // Ya trae la familia adelante: no hay nada que reconstruir.
        $this->assertSame([], $this->familias()->completarCodigo('REG17705', $rubros));

        // Sin rubro no se puede saber de qué familia es.
        $this->assertSame([], $this->familias()->completarCodigo('17705', []));
    }
}
