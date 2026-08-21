<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Aislamiento de la Zona de Clientes existente.
 *
 * Cubre un IDOR encontrado en el código de producción: tanto `/mis-datos` como
 * su POST resolvían el cliente desde el request (`User::find($request->id)` y
 * `User::find($request->cliente_id)`), así que cualquier cliente autenticado
 * podía LEER y MODIFICAR la ficha de otro —nombre, DNI, dirección, teléfono,
 * email y margen de reventa— cambiando un número en la URL o en un campo oculto
 * del formulario. Sin `?id`, además, la vista rompía con un 500.
 *
 * ⚠️ Esta clase apunta la conexión por defecto a la copia legacy, porque las
 * vistas del sitio actual consultan `imagenes`, `metadatos` y `categorias` al
 * renderizar el layout. Por eso NO puede usar RefreshDatabase: haría
 * migrate:fresh sobre el catálogo. El actor es el usuario `demo`, cuya ficha es
 * descartable y la regenera BmhDemoSeeder.
 */
final class ClientZoneIsolationTest extends TestCase
{
    private User $me;
    private User $other;

    protected function setUp(): void
    {
        parent::setUp();

        $legacy = (string) config('database.connections.mysql_legacy.database');

        // Guarda: sólo sobre una copia local.
        if (! str_contains($legacy, 'bmh_legacy')) {
            $this->markTestSkipped("La conexión legacy apunta a '{$legacy}'.");
        }

        // El layout del sitio actual necesita las tablas legacy.
        config([
            'database.connections.mysql.database' => $legacy,
            'database.connections.mysql.username' => env('DB_AI_USERNAME', 'bmh_app'),
            'database.connections.mysql.password' => env('DB_AI_PASSWORD', 'bmh_app_local'),
        ]);
        DB::purge('mysql');

        $demoId = DB::connection('mysql_legacy')->table('users')->where('username', 'demo')->value('id');

        if ($demoId === null) {
            $this->markTestSkipped('Falta el usuario demo: php artisan db:seed --class=BmhDemoSeeder');
        }

        $otherId = DB::connection('mysql_legacy')->table('users')
            ->where('rol', 'cliente')
            ->where('habilitado', 1)
            ->where('id', '<>', $demoId)
            ->orderBy('id')
            ->value('id');

        $this->me    = User::query()->findOrFail($demoId);
        $this->other = User::query()->findOrFail($otherId);
    }

    public function test_mis_datos_ignora_el_id_de_la_url(): void
    {
        $response = $this->actingAs($this->me)->get("/mis-datos?id={$this->other->id}");

        $response->assertOk();
        $response->assertSee('name="cliente_id" value="' . $this->me->id . '"', false);
        $response->assertDontSee('name="cliente_id" value="' . $this->other->id . '"', false);
    }

    public function test_mis_datos_no_rompe_sin_parametros(): void
    {
        $this->actingAs($this->me)->get('/mis-datos')->assertOk();
    }

    public function test_no_se_puede_modificar_la_ficha_de_otro_cliente(): void
    {
        $originalEmail   = $this->other->email;
        $originalReventa = $this->other->reventa;

        $this->actingAs($this->me)->post('/mis-datos-cliente', [
            'cliente_id'        => $this->other->id,
            'mail'              => 'atacante@example.com',
            'telefono'          => '1100000000',
            'direccionEntrega'  => 'Calle Falsa 123',
            'localidadEntregar' => 'Springfield',
            'incrementoReventa' => '999',
            'transporte'        => 'X',
        ]);

        $reloaded = User::query()->find($this->other->id);

        $this->assertSame($originalEmail, $reloaded->email, 'El email de otro cliente no puede cambiarse.');
        $this->assertSame($originalReventa, $reloaded->reventa, 'La reventa de otro cliente no puede cambiarse.');
    }
}
