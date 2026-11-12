<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use PHPUnit\Framework\Attributes\DataProvider;
use App\Models\Producto;
use App\Services\BusquedaPorEquivalencia;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Filtro «Por equivalencia» del buscador (sitio público y Zona de Clientes).
 *
 * Hermético: SQLite en memoria con el mínimo esquema (las tablas relacionales
 * de características). Los casos salen de errores reales: códigos de buje que
 * aparecían como equivalencias, la exacta ordenada sexta, y la Zona de
 * Clientes que no miraba las características.
 */
final class BusquedaPorEquivalenciaTest extends TestCase
{
    private BusquedaPorEquivalencia $busqueda;

    private const CAT_ALTERNADOR = 1;

    private const CAT_ARRANQUE = 2;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite_memoria',
            'database.connections.sqlite_memoria' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);
        DB::purge('sqlite_memoria');

        Schema::create('categorias', function (Blueprint $t) {
            $t->id();
            $t->string('nombre')->nullable();
        });
        Schema::create('productos', function (Blueprint $t) {
            $t->id();
            $t->string('codigo')->nullable();
            $t->string('nombre')->nullable();
            $t->integer('categoria_id')->nullable();
        });
        Schema::create('caracteristicas', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
        });
        Schema::create('caracteristica_producto', function (Blueprint $t) {
            $t->integer('producto_id');
            $t->integer('caracteristica_id');
            $t->string('valor');
            $t->primary(['producto_id', 'caracteristica_id']);
        });
        Schema::create('caracteristica_categoria', function (Blueprint $t) {
            $t->integer('categoria_id');
            $t->integer('caracteristica_id');
            $t->primary(['categoria_id', 'caracteristica_id']);
        });
        Schema::create('equivalencias', function (Blueprint $t) {
            $t->id();
            $t->integer('producto_id');
            $t->string('nombre')->nullable();
            $t->string('valor')->nullable();
        });

        DB::table('categorias')->insert([
            ['id' => self::CAT_ALTERNADOR, 'nombre' => 'Alternadores'],
            ['id' => self::CAT_ARRANQUE, 'nombre' => 'Arranques'],
        ]);
        DB::table('caracteristicas')->insert([
            ['id' => 10, 'nombre' => 'EQUIVALENCIA PORTAFICH'],
            ['id' => 11, 'nombre' => 'BUJE LADO COLECTOR'],
            ['id' => 12, 'nombre' => 'Nº ORIGINAL'],
            ['id' => 13, 'nombre' => 'CODIGO GV'],
            ['id' => 14, 'nombre' => 'EQUIVALENCIA NOSSO'],
        ]);
        // Las dos categorías declaran 10, 11 y 12; CODIGO GV sólo alternadores y NOSSO sólo arranques.
        foreach ([self::CAT_ALTERNADOR => [10, 11, 12, 13], self::CAT_ARRANQUE => [10, 11, 12, 14]] as $categoria => $ids) {
            foreach ($ids as $caracteristica) {
                DB::table('caracteristica_categoria')->insert(['categoria_id' => $categoria, 'caracteristica_id' => $caracteristica]);
            }
        }

        $this->busqueda = new BusquedaPorEquivalencia();
    }

    private function producto(string $codigo, int $categoria): int
    {
        return DB::table('productos')->insertGetId(['codigo' => $codigo, 'categoria_id' => $categoria]);
    }

    private function caracteristica(int $productoId, int $caracteristicaId, string $valor): void
    {
        DB::table('caracteristica_producto')->insert([
            'producto_id' => $productoId,
            'caracteristica_id' => $caracteristicaId,
            'valor' => $valor,
        ]);
    }

    /** @return list<string> códigos en el orden en que salen */
    private function buscar(string $termino): array
    {
        $coincidencias = $this->busqueda->coincidencias($termino);
        $query = Producto::query();
        $this->busqueda->filtrar($query, $coincidencias);
        $this->busqueda->ordenar($query, $coincidencias);

        return $query->orderBy('codigo')->pluck('codigo')->all();
    }

    // --------------------------------------------------------------- la regla

    /** @return array<string, array{string|null, bool}> */
    public static function nombresDeCampos(): array
    {
        return [
            'equivalencia de marca' => ['EQUIVALENCIA BOSCH 1', true],
            'código de marca (legacy)' => ['CODIGO GV', true],
            'código de componente' => ['CODIGO IMPULSOR ZEN', true],
            'código equivalente con tilde' => ['CÓDIGO EQUIVALENTE', true],
            'número original' => ['Nº ORIGINAL', true],
            'número original con grado' => ['N° ORIGINAL', true],
            'número original escrito' => ['NUMERO ORIGINAL', true],
            'reemplazo' => ['REEMPLAZA A ALT', true],
            'código de barras' => ['CÓDIGO DE BARRAS', false],
            'código de motor' => ['CÓDIGO DE MOTOR', false],
            'buje' => ['BUJE LADO COLECTOR', false],
            'medida' => ['DIAMETRO', false],
            'marca' => ['MARCA', false],
            'vacío' => ['', false],
            'null' => [null, false],
        ];
    }

    #[DataProvider('nombresDeCampos')]
    public function test_decide_que_campo_es_una_equivalencia(?string $nombre, bool $esperado): void
    {
        $this->assertSame($esperado, BusquedaPorEquivalencia::esCampoDeEquivalencia($nombre));
    }

    // ------------------------------------------- según la categoría del producto

    public function test_una_equivalencia_cuenta_solo_si_la_categoria_del_producto_la_declara(): void
    {
        // CODIGO GV es de alternadores; en un arranque el valor queda guardado pero no se ve en el sitio.
        $this->caracteristica($this->producto('ALT-1', self::CAT_ALTERNADOR), 13, '1261');
        $this->caracteristica($this->producto('ARR-1', self::CAT_ARRANQUE), 13, '1261');
        // EQUIVALENCIA NOSSO es de arranques.
        $this->caracteristica($this->producto('ALT-2', self::CAT_ALTERNADOR), 14, '9900');
        $this->caracteristica($this->producto('ARR-2', self::CAT_ARRANQUE), 14, '9900');

        $this->assertSame(['ALT-1'], $this->buscar('1261'));
        $this->assertSame(['ARR-2'], $this->buscar('9900'));
    }

    // ------------------------------------------------------- características

    public function test_busca_en_caracteristicas_de_equivalencia_y_no_en_atributos(): void
    {
        $portafich = $this->producto('IMPO1414', self::CAT_ARRANQUE);
        $this->caracteristica($portafich, 10, '1261');
        $buje = $this->producto('ARR-BUJE', self::CAT_ARRANQUE);
        $this->caracteristica($buje, 11, '1261');

        $this->assertSame(['IMPO1414'], $this->buscar('1261'));
    }

    public function test_ignora_caracteristicas_que_el_sitio_no_muestra(): void
    {
        // La categoría dejó de declarar la característica: el valor queda, pero no se busca.
        $oculta = $this->producto('OCULTA', self::CAT_ALTERNADOR);
        $this->caracteristica($oculta, 12, 'ZX-300');
        $this->assertSame(['OCULTA'], $this->buscar('ZX-300'));

        DB::table('caracteristica_categoria')->where('categoria_id', self::CAT_ALTERNADOR)->where('caracteristica_id', 12)->delete();

        $this->assertSame([], $this->buscar('ZX-300'));
    }

    public function test_busca_en_la_tabla_de_equivalencias(): void
    {
        $id = $this->producto('REG-1', self::CAT_ALTERNADOR);
        DB::table('equivalencias')->insert(['producto_id' => $id, 'nombre' => 'Valeo', 'valor' => 'CA-988/1']);

        $this->assertSame(['REG-1'], $this->buscar('CA988/1'));
        // El nombre de la marca no es un código.
        $this->assertSame([], $this->buscar('Valeo'));
    }

    // ------------------------------------------------------ coincidencia y orden

    public function test_ordena_exacta_primero_despues_empieza_y_al_final_contiene(): void
    {
        $contiene = $this->producto('A-CONTIENE', self::CAT_ARRANQUE);
        $this->caracteristica($contiene, 10, '81261090001');
        $empieza = $this->producto('B-EMPIEZA', self::CAT_ARRANQUE);
        $this->caracteristica($empieza, 10, '12610');
        $exacta = $this->producto('C-EXACTA', self::CAT_ARRANQUE);
        $this->caracteristica($exacta, 10, '1261');

        $this->assertSame(['C-EXACTA', 'B-EMPIEZA', 'A-CONTIENE'], $this->buscar('1261'));

        $grados = $this->busqueda->coincidencias('1261');
        $this->assertSame(BusquedaPorEquivalencia::EXACTA, $grados[$exacta]);
        $this->assertSame(BusquedaPorEquivalencia::EMPIEZA, $grados[$empieza]);
        $this->assertSame(BusquedaPorEquivalencia::CONTIENE, $grados[$contiene]);
    }

    public function test_ignora_espacios_guiones_puntos_y_mayusculas(): void
    {
        $id = $this->producto('REGR1690', self::CAT_ARRANQUE);
        $this->caracteristica($id, 12, 'RNI 1690');

        $this->assertSame(['REGR1690'], $this->buscar('rni-1690'));
        $this->assertSame(BusquedaPorEquivalencia::EXACTA, $this->busqueda->coincidencias('rni.1690')[$id]);
    }

    public function test_varios_codigos_en_un_campo_se_miran_de_a_uno(): void
    {
        $id = $this->producto('NFB080', self::CAT_ARRANQUE);
        $this->caracteristica($id, 12, '1006210111 -1006209818, 1006210149');

        // Uno de los códigos, exacto.
        $this->assertSame(BusquedaPorEquivalencia::EXACTA, $this->busqueda->coincidencias('1006209818')[$id]);
        // «11110062» sólo aparece pegando el final de un código con el
        // principio del siguiente: no es una coincidencia.
        $this->assertSame([], $this->buscar('11110062'));
    }

    public function test_un_termino_vacio_o_sin_coincidencias_no_trae_nada(): void
    {
        $id = $this->producto('ALGO', self::CAT_ARRANQUE);
        $this->caracteristica($id, 10, '1261');

        $this->assertSame([], $this->buscar(' - '));
        $this->assertSame([], $this->buscar('NOEXISTE'));
    }

    public function test_el_puntaje_para_el_buscador_publico_respeta_el_orden(): void
    {
        $this->assertGreaterThan(BusquedaPorEquivalencia::puntaje(BusquedaPorEquivalencia::EMPIEZA), BusquedaPorEquivalencia::puntaje(BusquedaPorEquivalencia::EXACTA));
        $this->assertGreaterThan(BusquedaPorEquivalencia::puntaje(BusquedaPorEquivalencia::CONTIENE), BusquedaPorEquivalencia::puntaje(BusquedaPorEquivalencia::EMPIEZA));
        $this->assertSame(0, BusquedaPorEquivalencia::puntaje(null));
        // Una equivalencia exacta le gana al máximo que suma la relevancia por
        // código, nombre, marca, modelo y categoría (1000+700+500+300+200).
        $this->assertGreaterThan(2700, BusquedaPorEquivalencia::puntaje(BusquedaPorEquivalencia::EXACTA));
    }
}
