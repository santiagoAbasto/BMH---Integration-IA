<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Producto;
use App\Services\BusquedaPorEquivalencia;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Filtro «Por equivalencia» del buscador (sitio público y Zona de Clientes).
 *
 * Hermético: SQLite en memoria con el mínimo esquema. Los casos salen de
 * errores reales: códigos de buje que aparecían como equivalencias, la exacta
 * ordenada sexta, y la Zona de Clientes que no miraba las características.
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

        // La misma columna vieja guarda cosas distintas según la categoría.
        Schema::create('categorias', function (Blueprint $t) {
            $t->id();
            $t->string('nombre')->nullable();
            $t->string('columna_1')->nullable();
            $t->string('columna_2')->nullable();
        });
        Schema::create('productos', function (Blueprint $t) {
            $t->id();
            $t->string('codigo')->nullable();
            $t->string('nombre')->nullable();
            $t->integer('categoria_id')->nullable();
            $t->string('columna_1')->nullable();
            $t->string('columna_2')->nullable();
        });
        Schema::create('caracteristicas', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
        });
        Schema::create('producto_caracteristica', function (Blueprint $t) {
            $t->id();
            $t->integer('producto_id');
            $t->integer('caracteristica_id');
            $t->string('valor')->nullable();
            $t->softDeletes();
        });
        Schema::create('categoria_caracteristica', function (Blueprint $t) {
            $t->id();
            $t->integer('categoria_id');
            $t->integer('caracteristica_id');
            $t->softDeletes();
        });
        Schema::create('equivalencias', function (Blueprint $t) {
            $t->id();
            $t->integer('producto_id');
            $t->string('nombre')->nullable();
            $t->string('valor')->nullable();
        });

        DB::table('categorias')->insert([
            ['id' => self::CAT_ALTERNADOR, 'nombre' => 'Alternadores', 'columna_1' => 'CODIGO GV', 'columna_2' => 'DIAMETRO'],
            ['id' => self::CAT_ARRANQUE, 'nombre' => 'Arranques', 'columna_1' => 'BUJE LADO COLECTOR', 'columna_2' => 'EQUIVALENCIA NOSSO'],
        ]);
        DB::table('caracteristicas')->insert([
            ['id' => 10, 'nombre' => 'EQUIVALENCIA PORTAFICH'],
            ['id' => 11, 'nombre' => 'BUJE LADO COLECTOR'],
            ['id' => 12, 'nombre' => 'Nº ORIGINAL'],
        ]);
        // Las dos categorías declaran las tres características.
        foreach ([self::CAT_ALTERNADOR, self::CAT_ARRANQUE] as $categoria) {
            foreach ([10, 11, 12] as $caracteristica) {
                DB::table('categoria_caracteristica')->insert(['categoria_id' => $categoria, 'caracteristica_id' => $caracteristica]);
            }
        }

        $this->busqueda = new BusquedaPorEquivalencia();
    }

    private function producto(string $codigo, int $categoria, array $columnas = []): int
    {
        return DB::table('productos')->insertGetId(['codigo' => $codigo, 'categoria_id' => $categoria, ...$columnas]);
    }

    private function caracteristica(int $productoId, int $caracteristicaId, string $valor, bool $borrada = false): void
    {
        DB::table('producto_caracteristica')->insert([
            'producto_id' => $productoId,
            'caracteristica_id' => $caracteristicaId,
            'valor' => $valor,
            'deleted_at' => $borrada ? now() : null,
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

    /** @dataProvider nombresDeCampos */
    public function test_decide_que_campo_es_una_equivalencia(?string $nombre, bool $esperado): void
    {
        $this->assertSame($esperado, BusquedaPorEquivalencia::esCampoDeEquivalencia($nombre));
    }

    // ----------------------------------------------------- columnas viejas

    public function test_una_columna_vieja_cuenta_solo_si_en_su_categoria_es_equivalencia(): void
    {
        // columna_1 es CODIGO GV en alternadores, pero BUJE en arranques.
        $this->producto('ALT-1', self::CAT_ALTERNADOR, ['columna_1' => '1261']);
        $this->producto('ARR-1', self::CAT_ARRANQUE, ['columna_1' => '1261']);
        // columna_2 es DIAMETRO en alternadores, EQUIVALENCIA NOSSO en arranques.
        $this->producto('ALT-2', self::CAT_ALTERNADOR, ['columna_2' => '9900']);
        $this->producto('ARR-2', self::CAT_ARRANQUE, ['columna_2' => '9900']);

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
        $borrada = $this->producto('BORRADA', self::CAT_ALTERNADOR);
        $this->caracteristica($borrada, 10, 'ZX-100', borrada: true);

        // Una fila vieja con otro valor quedó tapada por la última.
        $vieja = $this->producto('VIEJA', self::CAT_ALTERNADOR);
        $this->caracteristica($vieja, 10, 'ZX-200');
        $this->caracteristica($vieja, 10, 'ZX-201');

        // La categoría destildó la característica.
        $oculta = $this->producto('OCULTA', self::CAT_ALTERNADOR);
        $this->caracteristica($oculta, 12, 'ZX-300');
        DB::table('categoria_caracteristica')->where('categoria_id', self::CAT_ALTERNADOR)->where('caracteristica_id', 12)->update(['deleted_at' => now()]);

        $this->assertSame([], $this->buscar('ZX-100'));
        $this->assertSame([], $this->buscar('ZX-200'));
        $this->assertSame(['VIEJA'], $this->buscar('ZX-201'));
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
