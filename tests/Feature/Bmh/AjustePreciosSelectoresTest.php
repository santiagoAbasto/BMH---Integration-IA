<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use App\Models\Producto;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;

/**
 * Categorías › «Ajustar precios por porcentaje»: la categoría y el producto se eligen con un selector que busca
 * mientras se escribe. La página ya no trae los ~6.000 productos (el producto se busca en producto.buscar.partes,
 * por código o nombre, con su precio y el ajuste que ya tiene). Base local de solo lectura.
 */
final class AjustePreciosSelectoresTest extends BaseTestCase
{
    use CreatesApplication;

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
        config(['database.default' => $this->origDefault, 'database.connections.mysql' => $this->origMysql]);
        DB::purge('mysql');
        parent::tearDown();
    }

    private function admin(): Admin
    {
        $admin = new Admin(['name' => 'Tester', 'rol' => 'administrador']);
        $admin->id = 1;

        return $admin;
    }

    public function test_la_pagina_usa_los_selectores_con_busqueda_y_no_trae_todos_los_productos(): void
    {
        $producto = Producto::query()->whereNotNull('codigo')->where('codigo', '!=', '')->firstOrFail();

        $html = $this->actingAs($this->admin(), 'admin')->get(route('dashboard.categorias'))->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'data-selector-buscable'));
        $this->assertStringContainsString('data-url="'.route('producto.buscar.partes').'"', $html);
        $this->assertStringContainsString('Todas las categorías', $html);
        $this->assertStringNotContainsString('<option value="'.$producto->id.'">', $html, 'no lista los productos en un <select>');
        $this->assertStringNotContainsString("$('#producto_select').select2", $html);
    }

    public function test_la_busqueda_de_productos_trae_precio_y_ajuste_actual(): void
    {
        $producto = Producto::query()->whereNotNull('codigo')->where('codigo', '!=', '')->whereNotNull('precio')->firstOrFail();

        $datos = $this->actingAs($this->admin(), 'admin')
            ->getJson(route('producto.buscar.partes', ['q' => $producto->codigo]))
            ->assertOk()
            ->json('data');

        $encontrado = collect($datos)->firstWhere('id', $producto->id);
        $this->assertNotNull($encontrado, 'encuentra el producto por su código');
        $this->assertEqualsWithDelta((float) $producto->precio, $encontrado['precio'], 0.001);
        $this->assertArrayHasKey('aumento', $encontrado);
        $this->assertArrayHasKey('descuento', $encontrado);
        $this->assertLessThanOrEqual(8, count($datos));

        // Con menos de 2 letras no busca.
        $this->actingAs($this->admin(), 'admin')->getJson(route('producto.buscar.partes', ['q' => 'a']))->assertExactJson(['data' => []]);
    }
}
