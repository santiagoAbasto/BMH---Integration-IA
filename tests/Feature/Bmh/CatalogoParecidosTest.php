<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\CreatesApplication;

/**
 * Búsqueda permisiva del catálogo y los «más parecidos» cuando nada cumple
 * todo lo pedido.
 *
 * Igual que CatalogoTest: corre contra la copia local de la legacy, con una
 * categoría y una palabra inventadas para que los resultados sean exactos,
 * y borra todo al terminar.
 */
final class CatalogoParecidosTest extends BaseTestCase
{
    use CreatesApplication;

    private int $categoria;

    /** @var list<int> */
    private array $productos = [];

    private string $codigoConGuion = '';

    private string $origDefault = '';

    private array $origMysql = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->origDefault = config('database.default');
        $this->origMysql = config('database.connections.mysql');
        config([
            'database.connections.mysql.database' => 'bmh_legacy',
            'database.connections.mysql.username' => 'bmh_app',
            'database.connections.mysql.password' => 'bmh_app_local',
            'database.default' => 'mysql',
        ]);
        DB::purge('mysql');

        $this->categoria = DB::table('categorias')->insertGetId([
            'nombre' => 'ZZ CATEGORIA PARECIDOS '.uniqid(),
            'columna_1' => 'DIAMETRO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // «Zzturbina» no existe en el catálogo real: sólo aparecen estos.
        $this->codigoConGuion = 'ZZQ'.substr(uniqid(), -5).'-77';
        $this->crear('ZZTURBINA BOSCH', ['estado' => 1, 'marca' => 'BOSCH', 'columna_1' => '60MM']);
        $this->crear('ZZTURBINA VALEO', ['estado' => 2, 'marca' => 'VALEO', 'columna_1' => '60MM']);
        $this->crear('ZZTURBINA BOSCH CON POLEA', ['estado' => 1, 'marca' => 'BOSCH', 'columna_1' => '70MM', 'codigo' => $this->codigoConGuion]);
    }

    protected function tearDown(): void
    {
        DB::table('productos')->whereIn('id', $this->productos)->delete();
        DB::table('categorias')->where('id', $this->categoria)->delete();
        config(['database.default' => $this->origDefault, 'database.connections.mysql' => $this->origMysql]);
        DB::purge('mysql');
        parent::tearDown();
    }

    private function crear(string $nombre, array $datos): void
    {
        $this->productos[] = DB::table('productos')->insertGetId($datos + [
            'codigo' => 'ZZPAR'.substr(uniqid(), -6),
            'nombre' => $nombre,
            'orden' => 'zz',
            'descripcion' => 'test',
            'precio' => 100,
            'categoria_id' => $this->categoria,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function buscar(string $q, array $filtros = []): TestResponse
    {
        return $this->get(route('search', ['q' => $q] + $filtros))->assertOk();
    }

    /** @return list<string> */
    private static function nombres(iterable $productos): array
    {
        return collect($productos)->pluck('nombre')->sort()->values()->all();
    }

    // ------------------------------------------------------- más permisiva

    public function test_no_distingue_tildes_y_no_falla_con_ellas(): void
    {
        // Antes, cualquier tilde hacía fallar la consulta (columna armscii8).
        $this->assertCount(3, $this->buscar('zztúrbinas')->viewData('productos'));
    }

    public function test_el_codigo_se_encuentra_con_o_sin_espacios_y_guiones(): void
    {
        $sinGuion = str_replace('-', '', $this->codigoConGuion);

        $this->assertSame(['ZZTURBINA BOSCH CON POLEA'], self::nombres($this->buscar($sinGuion)->viewData('productos')));
        $this->assertSame(['ZZTURBINA BOSCH CON POLEA'], self::nombres($this->buscar(mb_strtolower($sinGuion))->viewData('productos')));
    }

    public function test_cada_palabra_trae_tambien_sus_sinonimos(): void
    {
        config(['catalogo.sinonimos' => [['zzturbina', 'zzsoplador']]]);

        $this->assertCount(3, $this->buscar('zzsoplador')->viewData('productos'));
        $this->assertSame(['ZZTURBINA BOSCH', 'ZZTURBINA BOSCH CON POLEA'], self::nombres($this->buscar('zzsopladores bosch')->viewData('productos')));
    }

    // --------------------------------------------------- los más parecidos

    public function test_un_error_de_tipeo_muestra_lo_que_quiso_buscar(): void
    {
        $respuesta = $this->buscar('zzturbna');

        $this->assertCount(0, $respuesta->viewData('productos'));
        $parecidos = $respuesta->viewData('parecidos');
        $this->assertSame('zzturbina', $parecidos['termino']);
        $this->assertCount(3, $parecidos['productos']);
        $respuesta->assertSee('Sin coincidencias exactas.')
            ->assertSee('Buscamos «<strong>zzturbina</strong>» en lugar de «zzturbna»', false);
    }

    public function test_con_filtros_que_nadie_cumple_muestra_los_que_cumplen_casi_todo(): void
    {
        // Bosch y reconstruido no hay: lo más cercano es dejar de lado el estado
        // (pesa menos que la marca), no la marca.
        $respuesta = $this->get(route('productos', ['categoria' => $this->categoria, 'marca' => 'bosch', 'estado' => ['reconstruido']]))->assertOk();

        $this->assertCount(0, $respuesta->viewData('productos'));
        $parecidos = $respuesta->viewData('parecidos');
        $this->assertSame(['ZZTURBINA BOSCH', 'ZZTURBINA BOSCH CON POLEA'], self::nombres($parecidos['productos']));
        $this->assertSame(['Reconstruido'], $parecidos['sin']);
        $respuesta->assertSee('No cumplen:')->assertSee('catalogo__aviso-filtro', false);

        // Valeo de 70 mm tampoco: el de Valeo de 60 mm, sin la medida.
        $parecidos = $this->get(route('productos', ['categoria' => $this->categoria, 'marca' => 'valeo', 'atributo' => ['columna_1' => '70MM']]))->viewData('parecidos');
        $this->assertSame(['ZZTURBINA VALEO'], self::nombres($parecidos['productos']));
        $this->assertSame(['Diametro: 70MM'], $parecidos['sin']);
        $this->assertStringContainsString('marca=VALEO', (string) $parecidos['url']);
        $this->assertStringNotContainsString('atributo', (string) $parecidos['url']);
    }

    public function test_si_coinciden_solo_algunas_palabras_lo_aclara(): void
    {
        $respuesta = $this->buscar('zzturbina valeo zzinexistente');

        $parecidos = $respuesta->viewData('parecidos');
        $this->assertTrue($parecidos['parcial']);
        // Los que tienen más palabras: «zzturbina» y «valeo».
        $this->assertSame(['ZZTURBINA VALEO'], self::nombres($parecidos['productos']));
        $this->assertNull($parecidos['url']);
        $respuesta->assertSee('Tienen parte de las palabras que buscaste.');
    }

    public function test_si_no_se_parece_a_nada_queda_el_aviso_de_siempre(): void
    {
        $respuesta = $this->buscar('zzqxwvj');

        $this->assertNull($respuesta->viewData('parecidos'));
        $respuesta->assertSee('No encontramos resultados')->assertDontSee('Sin coincidencias exactas.');
    }

    public function test_tambien_llegan_al_filtrar_sin_recargar(): void
    {
        $this->getJson(route('productos', ['categoria' => $this->categoria, 'marca' => 'bosch', 'estado' => ['reconstruido'], 'parcial' => 'contenido']))
            ->assertOk()
            ->assertSee('Sin coincidencias exactas.')
            ->assertSee('ZZTURBINA BOSCH CON POLEA');
    }
}
