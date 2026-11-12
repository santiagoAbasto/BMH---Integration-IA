<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;

/**
 * Zona de Clientes: un cliente sólo ve y repite SUS pedidos, aunque cambie el ?id de la dirección.
 * Base local, con dos clientes y un pedido de cada uno que se borran al terminar.
 */
final class ZonaClientesSeguridadTest extends BaseTestCase
{
    use CreatesApplication;

    /** @var array<string, int> */
    private array $clientes = [];

    /** @var array<string, int> */
    private array $pedidos = [];

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

        foreach (['A', 'B'] as $letra) {
            $sufijo = $letra.uniqid();
            $this->clientes[$letra] = DB::table('users')->insertGetId([
                'name' => 'ZZ Cliente '.$letra,
                'username' => 'zz-seguridad-'.$sufijo,
                'email' => 'zz-seguridad-'.$sufijo.'@example.invalid',
                'password' => bcrypt('x'),
                'rol' => 'cliente',
                'habilitado' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->pedidos[$letra] = DB::table('pedidos')->insertGetId([
                'fecha' => '12/11/2026', 'nombre' => 'ZZ '.$letra, 'dni' => '-', 'mail' => '-', 'provincia' => '-',
                'localidad' => '-', 'direccion' => '-', 'celular' => '-', 'cp' => '-',
                'total_pedido' => $letra === 'A' ? '111,11' : '999.999,99', 'archivo' => 0,
                'estado' => 'Pendiente', 'estado_orden' => 'Pendiente', 'cliente_id' => $this->clientes[$letra],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    protected function tearDown(): void
    {
        DB::table('pedidos')->whereIn('id', $this->pedidos)->delete();
        DB::table('users')->whereIn('id', $this->clientes)->delete();
        config(['database.default' => $this->origDefault, 'database.connections.mysql' => $this->origMysql]);
        DB::purge('mysql');
        parent::tearDown();
    }

    private function comoA(): static
    {
        return $this->actingAs(User::findOrFail($this->clientes['A']), 'web');
    }

    public function test_el_historial_muestra_solo_los_pedidos_propios_aunque_cambie_el_id(): void
    {
        $html = $this->comoA()->get(route('cliente.historial', ['id' => $this->clientes['B']]))->assertOk()->getContent();

        $this->assertStringContainsString('111,11', $html);
        $this->assertStringNotContainsString('999.999,99', $html);
    }

    public function test_no_puede_ver_el_detalle_de_un_pedido_ajeno(): void
    {
        $this->comoA()->get(route('cliente.pedido', ['id' => $this->pedidos['B']]))->assertNotFound();
        $this->comoA()->get(route('cliente.pedido', ['id' => $this->pedidos['A']]))->assertOk();
    }

    public function test_no_puede_repetir_un_pedido_ajeno(): void
    {
        $this->comoA()->post(route('repetir.pedido'), ['id' => $this->pedidos['B']])->assertNotFound();
        $this->comoA()->post(route('repetir.pedido'), ['id' => $this->pedidos['A']])->assertOk();
    }
}
