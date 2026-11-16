<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Http\Controllers\AparienciaController;
use App\Models\Admin;
use App\Models\Apariencia;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;

/**
 * Detalle de una novedad (rediseño) y la vista previa de admin → Extras › Header / Footer, que muestra el header y el
 * footer reales del sitio. Base local (sólo lectura).
 */
final class NovedadDetalleYVistaPreviaTest extends BaseTestCase
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
        $admin = new Admin(['name' => 'Bruno Tester', 'rol' => 'administrador']);
        $admin->id = 1;

        return $admin;
    }

    public function test_el_detalle_de_una_novedad_tiene_cabecera_texto_compartir_y_mas_novedades(): void
    {
        $novedad = DB::table('novedades')->orderBy('id')->first();
        if ($novedad === null) {
            $this->markTestSkipped('No hay novedades en la base local.');
        }

        $html = $this->get(route('novedad', ['id' => $novedad->id]))->assertOk()->getContent();

        $this->assertStringContainsString('id="bmh-novedad-titulo"', $html);
        $this->assertStringContainsString('class="bmh-novedad__texto"', $html);
        $this->assertStringContainsString('<time datetime=', $html);
        $this->assertStringContainsString('data-copiar-enlace="'.route('novedad', ['id' => $novedad->id]).'"', $html);
        $this->assertStringContainsString('https://wa.me/?text=', $html);
        $this->assertStringContainsString('Volver a novedades', $html);
        // Migas: Inicio › Novedades › el título.
        $this->assertStringContainsString('href="'.route('novedades').'"', $html);

        if (DB::table('novedades')->count() > 1) {
            $this->assertStringContainsString('Más novedades', $html);
            // No se repite la que se está leyendo.
            $this->assertStringNotContainsString('<a href="'.route('novedad', ['id' => $novedad->id]).'" class="osole-post-card', $html);
        }
    }

    public function test_una_novedad_que_no_existe_da_404(): void
    {
        $this->get(route('novedad', ['id' => 999999]))->assertNotFound();
    }

    public function test_la_vista_previa_muestra_el_header_real_en_cada_estado(): void
    {
        foreach (AparienciaController::ESTADOS_HEADER as $estado) {
            $html = $this->actingAs($this->admin(), 'admin')
                ->get(route('extras.vista-previa', ['pieza' => 'header', 'estado' => $estado]))
                ->assertOk()
                ->getContent();

            $this->assertStringContainsString('id="site-header"', $html, $estado);
            $this->assertStringContainsString('class="ext-vp ext-vp--header"', $html, $estado);
        }

        // Los links de la Home al cargar toman lo que se edita.
        $this->actingAs($this->admin(), 'admin')
            ->get(route('extras.vista-previa', ['pieza' => 'header', 'estado' => 'inicio']))
            ->assertSee('--bmh-portada-links: var('.Apariencia::variable('transparente_reposo', 'links').')', false)
            ->assertSee("header.classList.add('bmh-header--home'", false);
    }

    public function test_la_vista_previa_del_footer_es_el_footer_real(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->get(route('extras.vista-previa', ['pieza' => 'footer']))
            ->assertOk()
            ->assertSee('id="site-footer"', false)
            ->assertSee('data-logo-estado', false)
            ->assertDontSee('id="site-header"', false);
    }

    public function test_la_vista_previa_es_solo_para_el_admin_y_solo_header_o_footer(): void
    {
        $this->get(route('extras.vista-previa', ['pieza' => 'header']))->assertRedirect();
        $this->actingAs($this->admin(), 'admin')->get('/dashboard/extras/vista-previa/otra')->assertNotFound();
    }
}
