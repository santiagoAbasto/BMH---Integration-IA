<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;

/**
 * Buscador del header: sugerencias mientras se escribe y página /buscar.
 *
 * Igual que SearchNewTablesTest: corre contra la copia local de la legacy,
 * con productos de prueba de códigos únicos que se borran al terminar.
 */
final class BuscadorHeaderTest extends BaseTestCase
{
    use CreatesApplication;

    /** @var list<int> */
    private array $productos = [];

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
    }

    protected function tearDown(): void
    {
        if ($this->productos !== []) {
            DB::table('equivalencias')->whereIn('producto_id', $this->productos)->delete();
            DB::table('productos')->whereIn('id', $this->productos)->delete();
        }
        config(['database.default' => $this->origDefault, 'database.connections.mysql' => $this->origMysql]);
        DB::purge('mysql');
        parent::tearDown();
    }

    private function crearProducto(string $codigo, array $datos = []): int
    {
        $id = DB::table('productos')->insertGetId($datos + [
            'codigo' => $codigo,
            'nombre' => 'PRODUCTO DE PRUEBA '.$codigo,
            'orden' => 'zz',
            'descripcion' => 'test',
            'precio' => 100,
            'marca' => 'TESTMARCA',
            'modelo' => 'TESTMODELO',
            'estado' => 1,
            'categoria_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->productos[] = $id;
    }

    private function unico(string $prefijo): string
    {
        return $prefijo.strtoupper(substr(uniqid(), -6));
    }

    /** @return list<int> */
    private function idsSugeridos(string $q): array
    {
        return collect($this->getJson(route('search.sugerencias', ['q' => $q]))->assertOk()->json('productos'))
            ->pluck('id')->all();
    }

    public function test_sugiere_por_codigo_y_devuelve_lo_que_muestra_el_desplegable(): void
    {
        $codigo = $this->unico('ZZHDR');
        $id = $this->crearProducto($codigo);

        $this->getJson(route('search.sugerencias', ['q' => $codigo]))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('productos.0.id', $id)
            ->assertJsonPath('productos.0.codigo', $codigo)
            ->assertJsonPath('productos.0.estado', 'Nuevo')
            ->assertJsonPath('productos.0.url', route('producto', ['id' => $id]))
            ->assertJsonPath('ver_todos', route('search', ['q' => $codigo]))
            ->assertJsonStructure(['productos' => [['imagen', 'nombre', 'marca', 'categoria']]])
            // Nunca precios: dependen de quién mira.
            ->assertJsonMissingPath('productos.0.precio');
    }

    public function test_encuentra_por_equivalencia(): void
    {
        $equivalencia = $this->unico('ZZEQV');
        $id = $this->crearProducto($this->unico('ZZHDR'));
        DB::table('equivalencias')->insert(['producto_id' => $id, 'nombre' => 'DIPRA', 'valor' => $equivalencia, 'orden' => 0]);

        $this->assertContains($id, $this->idsSugeridos($equivalencia));
    }

    public function test_encuentra_por_atributo_de_las_columnas_viejas(): void
    {
        $medida = $this->unico('ZZMED');
        $id = $this->crearProducto($this->unico('ZZHDR'), ['columna_1' => $medida]);

        $this->assertContains($id, $this->idsSugeridos($medida));
    }

    public function test_cada_palabra_tiene_que_aparecer_y_el_orden_no_importa(): void
    {
        $marca = $this->unico('ZZMARCA');
        $modelo = $this->unico('ZZMODELO');
        $ambos = $this->crearProducto($this->unico('ZZHDR'), ['marca' => $marca, 'modelo' => $modelo]);
        $soloMarca = $this->crearProducto($this->unico('ZZHDR'), ['marca' => $marca]);

        $ids = $this->idsSugeridos("{$modelo} {$marca}");

        $this->assertContains($ambos, $ids);
        $this->assertNotContains($soloMarca, $ids);
    }

    public function test_el_codigo_exacto_va_primero(): void
    {
        $codigo = $this->unico('ZZHDR');
        $contiene = $this->crearProducto($codigo.'X');
        $exacto = $this->crearProducto($codigo);

        $this->assertSame([$exacto, $contiene], $this->idsSugeridos($codigo));
    }

    public function test_no_ofrece_productos_ocultos(): void
    {
        $codigo = $this->unico('ZZHDR');
        $oculto = $this->crearProducto($codigo, ['estado' => 0]);

        $this->assertNotContains($oculto, $this->idsSugeridos($codigo));
    }

    public function test_con_menos_de_dos_caracteres_no_busca(): void
    {
        $this->getJson(route('search.sugerencias', ['q' => 'a']))
            ->assertOk()
            ->assertJsonPath('total', 0)
            ->assertJsonPath('productos', []);
    }

    public function test_la_pagina_de_resultados_pagina_y_conserva_el_termino(): void
    {
        $codigo = $this->unico('ZZHDR');
        $id = $this->crearProducto($codigo);

        $respuesta = $this->get(route('search', ['q' => $codigo]))->assertOk();

        $this->assertSame($codigo, $respuesta->viewData('termino'));
        $this->assertSame([$id], $respuesta->viewData('productos')->pluck('id')->all());
        $respuesta->assertSee('value="'.$codigo.'"', false); // queda escrito en el header
    }

    public function test_acepta_el_parametro_search_de_los_links_viejos(): void
    {
        $codigo = $this->unico('ZZHDR');
        $id = $this->crearProducto($codigo);

        $respuesta = $this->get('/buscar?search='.$codigo)->assertOk();

        $this->assertSame([$id], $respuesta->viewData('productos')->pluck('id')->all());
    }

    public function test_sin_resultados_muestra_la_ayuda(): void
    {
        $this->get(route('search', ['q' => $this->unico('ZZNADA')]))
            ->assertOk()
            ->assertSee('No encontramos resultados');
    }

    public function test_la_home_ya_no_tiene_el_filtro_viejo_y_el_header_tiene_el_buscador(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('name="buscadorPrincipal"', false)
            ->assertDontSee('name="codigoBMH"', false)
            ->assertSee('id="buscador-header"', false)
            ->assertSee(route('search.sugerencias'), false);
    }
}
