<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use App\Models\Producto;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;

/**
 * Ofertas de la zona pública: el admin marca o desmarca un producto desde el listado y /ofertas
 * muestra el catálogo sólo con esos productos. Como CatalogoTest: base local, con una categoría y
 * productos propios que se borran al terminar.
 */
final class OfertasTest extends BaseTestCase
{
    use CreatesApplication;

    private int $categoria;

    /** @var array<string, int> */
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

        $this->categoria = DB::table('categorias')->insertGetId([
            'nombre' => 'ZZ OFERTAS DE PRUEBA '.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['A' => true, 'B' => true, 'C' => false] as $letra => $oferta) {
            $this->productos[$letra] = DB::table('productos')->insertGetId([
                'codigo' => 'ZZOF'.$letra.substr(uniqid(), -5),
                'nombre' => 'Producto de oferta '.$letra,
                'categoria_id' => $this->categoria,
                'orden' => 'zz',
                'estado' => 1,
                'oferta' => $oferta,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function tearDown(): void
    {
        DB::table('productos')->whereIn('id', $this->productos)->delete();
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

    public function test_el_admin_marca_y_desmarca_una_oferta(): void
    {
        $id = $this->productos['C'];

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('producto.oferta'), ['producto_id' => $id])
            ->assertOk()
            ->assertJson(['producto_id' => $id, 'oferta' => true]);
        $this->assertTrue(Producto::findOrFail($id)->oferta);

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('producto.oferta'), ['producto_id' => $id, 'oferta' => false])
            ->assertOk()
            ->assertJson(['oferta' => false]);
        $this->assertFalse(Producto::findOrFail($id)->oferta);
    }

    public function test_un_visitante_no_puede_marcar_ofertas(): void
    {
        $this->post(route('producto.oferta'), ['producto_id' => $this->productos['C']]);

        $this->assertFalse(Producto::findOrFail($this->productos['C'])->oferta);
    }

    public function test_ofertas_muestra_solo_los_productos_en_oferta(): void
    {
        $html = $this->get(route('ofertas', ['categoria' => $this->categoria]))->assertOk()->getContent();

        $this->assertStringContainsString('<h1 class="catalogo__titulo">Ofertas</h1>', $html);
        $this->assertMatchesRegularExpression('#<strong>2</strong>\s*productos\s*en oferta#', $html);
        $this->assertStringContainsString('Producto de oferta A', $html);
        $this->assertStringContainsString('Producto de oferta B', $html);
        $this->assertStringNotContainsString('Producto de oferta C', $html);
        $this->assertSame(2, substr_count($html, 'pbmh-card--oferta'));
    }
}
