<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use App\Support\Panel\MetricasPanel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;

/**
 * Inicio del panel (como Conelec): actividad con rango, «Te esperan» y estado del sitio; y la campana del topbar con
 * pedidos sin abrir, consultas sin leer y clientes nuevos. Base local; lo que se marca se deja como estaba.
 */
final class PanelInicioTest extends BaseTestCase
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

    public function test_el_inicio_muestra_actividad_pendientes_y_estado(): void
    {
        $html = $this->actingAs($this->admin(), 'admin')->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('data-panel', $html);
        $this->assertStringContainsString(', Bruno.', $html);
        foreach (['data-kpi="facturacion"', 'data-kpi="pedidos"', 'data-kpi="clientes"', 'data-kpi="consultas"', 'data-panel-datos', 'Estado del sitio', 'Últimos pedidos'] as $parte) {
            $this->assertStringContainsString($parte, $html);
        }
        // La campana está en el topbar de todo el panel.
        $this->assertStringContainsString('data-campana', $html);
    }

    public function test_la_campana_cuenta_pedidos_sin_abrir_y_clientes_nuevos(): void
    {
        $pedido = DB::table('pedidos')->orderByDesc('id')->first(['id', 'visto_at']);
        $cliente = DB::table('users')->where('rol', 'cliente')->orderByDesc('id')->first(['id', 'revisado_at']);

        try {
            DB::table('pedidos')->where('id', $pedido->id)->update(['visto_at' => null]);
            DB::table('users')->where('id', $cliente->id)->update(['revisado_at' => null]);

            $datos = $this->actingAs($this->admin(), 'admin')->getJson(route('panel.notificaciones'))->assertOk()->json();
            $claves = array_column($datos['avisos'], 'key');
            $this->assertContains('pedido-'.$pedido->id, $claves);
            $this->assertContains('cliente-'.$cliente->id, $claves);
            $this->assertGreaterThanOrEqual(2, $datos['sin_ver']);
            $this->assertStringContainsString('Pedido #'.$pedido->id, $datos['html']);

            // Revisar el cliente desde la campana lo saca de la lista.
            $this->actingAs($this->admin(), 'admin')->get(route('panel.cliente.revisar', $cliente->id))->assertRedirect(route('dashboard.clientes'));
            $this->assertNotNull(DB::table('users')->where('id', $cliente->id)->value('revisado_at'));
        } finally {
            DB::table('pedidos')->where('id', $pedido->id)->update(['visto_at' => $pedido->visto_at]);
            DB::table('users')->where('id', $cliente->id)->update(['revisado_at' => $cliente->revisado_at]);
        }
    }

    public function test_un_visitante_no_ve_la_campana(): void
    {
        // El middleware admin manda al login: no devuelve los avisos.
        $this->get(route('panel.notificaciones'))->assertRedirect();
    }

    public function test_el_total_del_pedido_se_lee_con_el_formato_argentino(): void
    {
        $this->assertSame(327290.48, MetricasPanel::importe('327.290,48'));
        $this->assertSame(5695919.56, MetricasPanel::importe('5.695.919,56'));
        $this->assertSame(1200.5, MetricasPanel::importe('1200.50'));
        $this->assertSame(0.0, MetricasPanel::importe(null));
    }
}
