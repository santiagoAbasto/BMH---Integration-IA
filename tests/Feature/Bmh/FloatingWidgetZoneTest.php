<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * La esquina inferior derecha tiene un solo dueño según la SESIÓN.
 *
 *   Visitante sin sesión, o cliente fuera de la beta → WhatsApp.
 *   Cliente o vendedor logueado y habilitado         → Asesor IA, en TODO el sitio.
 *
 * El asesor acompaña al cliente logueado también en las páginas públicas (home,
 * "Nosotros", catálogo) y al recargar, para que no desaparezca al navegar. La
 * condición es `shouldRender()` —sesión de cliente/vendedor + acceso a la beta—,
 * ya no `$zonaclientes`.
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
        // El botón es osole::whatsapp-button.v1 (como Conelec), con la clase bmh-wa para los colores y el latido.
        $response->assertSee('class="osole-whatsapp-button-v1 bmh-wa"', false);
        $response->assertSee('Escribinos ahora');
        $response->assertDontSee('id="bmh-advisor"', false);
    }

    private function assertAdvisor($response): void
    {
        $response->assertOk();
        $response->assertSee('id="bmh-advisor"', false);
        $response->assertDontSee('osole-whatsapp-button-v1', false);
    }

    public function test_visitante_sin_sesion_ve_whatsapp(): void
    {
        $this->customer(); // sólo para configurar la conexión

        $this->assertWhatsapp($this->get('/'));
        $this->assertWhatsapp($this->get('/nosotros'));
    }

    public function test_cliente_logueado_ve_el_asesor_tambien_en_paginas_publicas(): void
    {
        // El asesor acompaña al cliente logueado en todo el sitio: en la home y en
        // "Nosotros" ve el Asesor IA, no WhatsApp. Así no se pierde al navegar o
        // recargar y caer en una vista pública.
        $this->assertAdvisor($this->actingAs($this->customer())->get('/'));
        $this->assertAdvisor($this->actingAs($this->customer())->get('/nosotros'));
    }

    public function test_en_la_zona_de_clientes_ve_el_asesor_y_no_whatsapp(): void
    {
        $me = $this->customer();

        foreach (['/productos-zona-home', '/carrito', '/mis-datos'] as $path) {
            $this->assertAdvisor($this->actingAs($me)->get($path));
        }
    }

    public function test_en_la_zona_de_clientes_el_header_no_lleva_nosotros_ni_contacto(): void
    {
        $privada = $this->actingAs($this->customer())->get(route('productos.clientes'))->assertOk();
        $privada->assertDontSee('class="osole-header-catalog-v1__topbar-link"', false);

        // En la zona pública (aunque tenga sesión) siguen estando.
        $publica = $this->actingAs($this->customer())->get(route('nosotros'))->assertOk();
        $publica->assertSee('osole-header-catalog-v1__topbar-link', false);
        $publica->assertSee('>Contacto</a>', false);
    }

    public function test_el_logo_lleva_siempre_a_la_home_sin_cerrar_la_sesion(): void
    {
        $me = $this->customer();
        $logo = 'href="'.route('home').'" class="osole-header-catalog-v1__logo"';

        // Desde la Zona de Clientes también (antes llevaba al inicio de la zona).
        foreach ([route('productos.clientes'), route('carrito'), route('nosotros')] as $url) {
            $this->actingAs($me)->get($url)->assertOk()->assertSee($logo, false);
        }

        // La Home con sesión: sigue logueado y tiene a mano la Zona de Clientes.
        $this->actingAs($me)->get(route('home'))->assertOk()->assertSee('Ir a la Zona de Clientes');
        $this->assertAuthenticatedAs($me, 'web');
    }

    public function test_el_item_del_header_abre_el_asesor_sin_navegar(): void
    {
        $response = $this->actingAs($this->customer())->get('/productos-zona-home');

        $response->assertOk();
        // `data-bmh-advisor` + href="#": no es una URL nueva.
        $response->assertSee('data-bmh-advisor', false);
    }

    public function test_con_la_beta_cerrada_un_cliente_fuera_de_la_lista_no_ve_ningun_acceso_al_asesor(): void
    {
        $me = $this->customer();
        config(['bmh.assistant_access.mode' => 'allowlist', 'bmh.assistant_access.customer_ids' => [999999999]]);

        // Ni el ítem del menú ni el widget: ve WhatsApp, como antes de la IA.
        foreach (['/productos-zona-home', '/carrito'] as $path) {
            $response = $this->actingAs($me)->get($path);
            $this->assertWhatsapp($response);
            $response->assertDontSee('data-bmh-advisor', false);
        }
        $this->actingAs($me)->get('/asesor')->assertNotFound();
    }

    public function test_con_la_beta_cerrada_el_cliente_de_la_lista_ve_el_asesor(): void
    {
        $me = $this->customer();
        config(['bmh.assistant_access.mode' => 'allowlist', 'bmh.assistant_access.customer_ids' => [$me->id]]);

        $response = $this->actingAs($me)->get('/productos-zona-home');
        $this->assertAdvisor($response);
        $response->assertSee('data-bmh-advisor', false);
    }

    public function test_los_modales_del_sitio_tienen_su_funcion_para_abrirse(): void
    {
        // El carrito, la home y los listados abren sus modales con bmhAbrirModal().
        $this->actingAs($this->customer())->get('/carrito')
            ->assertOk()
            ->assertSee('window.bmhAbrirModal', false)
            ->assertSee('window.bmhCerrarModal', false);
    }

    public function test_el_carrito_publico_abre_aunque_se_entre_directo(): void
    {
        $this->customer(); // sólo para configurar la conexión

        // Sin página de origen fallaba (usaba $_SERVER['HTTP_REFERER'] sin más).
        $this->get('/carrito-publico')->assertOk()->assertSee("href='".route('productos')."'", false);

        $desde = url('/productos?categoria=48');
        $this->get('/carrito-publico', ['referer' => $desde])->assertOk()->assertSee("href='".$desde."'", false);
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
