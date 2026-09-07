<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * La esquina inferior derecha tiene un solo dueño según la ZONA.
 *
 *   Zona pública  → WhatsApp (el visitante todavía no es cliente).
 *   Zona Clientes → Asesor IA (ya está autenticado y se le puede cotizar).
 *
 * El punto fino: la zona la define la PÁGINA, no la sesión. Un cliente logueado
 * que entra a la home o a "Nosotros" está en la zona pública y le corresponde
 * WhatsApp. Por eso la condición mira `$zonaclientes` —la bandera que el propio
 * sitio ya usa— y no sólo `Auth::check()`.
 */
final class FloatingWidgetZoneTest extends TestCase
{
    private function customer(): User
    {
        $legacy = (string) config('database.connections.mysql_legacy.database');

        if (! str_contains($legacy, 'bmh_legacy')) {
            $this->markTestSkipped("La conexión legacy apunta a '{$legacy}'.");
        }

        // Las vistas del sitio consultan tablas legacy al renderizar el layout.
        config([
            'database.connections.mysql.database' => $legacy,
            'database.connections.mysql.username' => env('DB_AI_USERNAME', 'bmh_app'),
            'database.connections.mysql.password' => env('DB_AI_PASSWORD', 'bmh_app_local'),
        ]);
        DB::purge('mysql');

        $id = DB::connection('mysql_legacy')->table('users')->where('username', 'demo')->value('id');

        if ($id === null) {
            $this->markTestSkipped('Falta el usuario demo: php artisan db:seed --class=BmhDemoSeeder');
        }

        return User::query()->findOrFail($id);
    }

    private function assertWhatsapp($response): void
    {
        $response->assertOk();
        // El botón nuevo usa clases propias `bmh-wa-*`: las viejas
        // (.whatsapp-container / .whatsapp-btn) tenían CSS roto que lo dejaba
        // cortado contra el borde del viewport.
        $response->assertSee('class="bmh-wa"', false);
        $response->assertDontSee('id="bmh-advisor"', false);
    }

    private function assertAdvisor($response): void
    {
        $response->assertOk();
        $response->assertSee('id="bmh-advisor"', false);
        $response->assertDontSee('class="bmh-wa"', false);
    }

    public function test_visitante_sin_sesion_ve_whatsapp(): void
    {
        $this->customer(); // sólo para configurar la conexión

        $this->assertWhatsapp($this->get('/'));
        $this->assertWhatsapp($this->get('/nosotros'));
    }

    public function test_cliente_logueado_en_una_pagina_publica_tambien_ve_whatsapp(): void
    {
        // El caso que se escapaba: estar logueado no convierte la home en Zona
        // de Clientes.
        $this->assertWhatsapp($this->actingAs($this->customer())->get('/'));
        $this->assertWhatsapp($this->actingAs($this->customer())->get('/nosotros'));
    }

    public function test_en_la_zona_de_clientes_ve_el_asesor_y_no_whatsapp(): void
    {
        $me = $this->customer();

        foreach (['/productos-zona-home', '/carrito', '/mis-datos'] as $path) {
            $this->assertAdvisor($this->actingAs($me)->get($path));
        }
    }

    public function test_el_item_del_header_abre_el_asesor_sin_navegar(): void
    {
        $response = $this->actingAs($this->customer())->get('/productos-zona-home');

        $response->assertOk();
        // `data-bmh-advisor` + href="#": no es una URL nueva.
        $response->assertSee('data-bmh-advisor', false);
    }

    public function test_el_carrito_no_tiene_caracteres_rotos(): void
    {
        $response = $this->actingAs($this->customer())->get('/carrito');

        $response->assertOk();
        $response->assertSee('Artículo', false);
        $response->assertSee('Descripción', false);
        // U+FFFD: el acento perdido en una conversión previa.
        $response->assertDontSee("\u{FFFD}", false);
    }
}
