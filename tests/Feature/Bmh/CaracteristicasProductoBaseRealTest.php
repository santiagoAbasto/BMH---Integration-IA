<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use App\Models\Producto;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;

/**
 * Contra la base real (no SQLite): `productos` tenía una columna vieja `caracteristicas` que tapaba la relación
 * Producto::caracteristicas() y el admin mostraba el producto sin características. Crea sus datos y los borra.
 */
final class CaracteristicasProductoBaseRealTest extends BaseTestCase
{
    use CreatesApplication;

    private string $origDefault = '';

    private array $origMysql = [];

    private int $categoria;

    private int $producto;

    /** @var list<int> */
    private array $caracteristicas = [];

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

        $this->categoria = DB::table('categorias')->insertGetId(['nombre' => 'ZZ CARACT REAL '.uniqid(), 'created_at' => now(), 'updated_at' => now()]);
        $this->producto = DB::table('productos')->insertGetId([
            'codigo' => 'ZZCR'.substr(uniqid(), -6), 'nombre' => 'Producto prueba características', 'categoria_id' => $this->categoria,
            'orden' => 'zz', 'estado' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (['ZZ VOLTAJE PRUEBA '.uniqid() => '12V', 'ZZ GIRO PRUEBA '.uniqid() => 'HORARIO'] as $nombre => $valor) {
            $id = DB::table('caracteristicas')->insertGetId(['nombre' => $nombre, 'created_at' => now(), 'updated_at' => now()]);
            $this->caracteristicas[] = $id;
            DB::table('caracteristica_producto')->insert(['producto_id' => $this->producto, 'caracteristica_id' => $id, 'valor' => $valor, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    protected function tearDown(): void
    {
        DB::table('caracteristica_producto')->where('producto_id', $this->producto)->delete();
        DB::table('caracteristicas')->whereIn('id', $this->caracteristicas)->delete();
        DB::table('productos')->where('id', $this->producto)->delete();
        DB::table('categorias')->where('id', $this->categoria)->delete();
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

    public function test_la_relacion_devuelve_las_caracteristicas_y_no_una_columna(): void
    {
        $this->assertCount(2, Producto::query()->findOrFail($this->producto)->caracteristicas);
    }

    public function test_editar_producto_muestra_las_filas_cargadas(): void
    {
        $html = $this->actingAs($this->admin(), 'admin')
            ->get(route('producto.edit', ['id' => $this->producto]))->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'class="car-fila"'));
        $this->assertStringContainsString('value="12V"', $html);
        $this->assertStringContainsString('value="HORARIO"', $html);
    }

    public function test_copiar_de_otro_producto_trae_sus_valores(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->getJson(route('caracteristicas.de-producto', $this->producto))
            ->assertOk()
            ->assertJsonCount(2, 'caracteristicas')
            ->assertJsonFragment(['valor' => '12V']);
    }
}
