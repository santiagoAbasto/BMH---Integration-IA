<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * El sitio navega como una SPA (Turbo, como Conelec) en todas las páginas, no sólo en algunas:
 *
 *   - ninguna página pide la carga completa por defecto (antes sólo 7 tenían Turbo);
 *   - el CSS propio de cada página se marca data-turbo-track="dynamic" para que Turbo lo saque al salir de ella
 *     (si no, se acumulaba en el head y afectaba a las páginas siguientes);
 *   - las páginas que su JS modifica (catálogo, ficha, carrito, checkout) no se guardan en la caché de Turbo;
 *   - las librerías de una sola página (Fotorama, Select2) ya no se cargan en todas, y las que no se usaban
 *     (MercadoPago, loading.io) no se cargan más.
 */
final class SpaSitioTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $legacy = (string) config('database.connections.mysql_legacy.database');
        if (! str_contains($legacy, 'bmh_legacy')) {
            $this->markTestSkipped("La conexión legacy apunta a '{$legacy}'.");
        }

        config([
            'database.connections.mysql.database' => $legacy,
            'database.connections.mysql.username' => env('DB_AI_USERNAME', 'bmh_app'),
            'database.connections.mysql.password' => env('DB_AI_PASSWORD', 'bmh_app_local'),
        ]);
        DB::purge('mysql');
    }

    public function test_las_paginas_publicas_navegan_sin_recargar(): void
    {
        foreach (['/', '/productos', '/ofertas', '/nosotros', '/contacto', '/novedades', '/categorias'] as $url) {
            $respuesta = $this->get($url);
            $respuesta->assertOk();
            $respuesta->assertDontSee('name="turbo-visit-control"', false);
        }
    }

    public function test_el_catalogo_y_la_ficha_no_se_guardan_en_la_cache_de_turbo(): void
    {
        $this->get('/productos')->assertOk()->assertSee('<meta name="turbo-cache-control" content="no-cache">', false);

        $id = DB::connection('mysql_legacy')->table('productos')->value('id');
        if ($id !== null) {
            $ficha = $this->get('/producto?id='.$id);
            $ficha->assertOk();
            $ficha->assertSee('<meta name="turbo-cache-control" content="no-cache">', false);
            // Fotorama llega con la ficha, y su CSS sale del head al dejarla.
            $ficha->assertSee('fotorama/4.6.4/fotorama.js', false);
            $ficha->assertSee('<link data-turbo-track="dynamic" href="https://cdnjs.cloudflare.com/ajax/libs/fotorama', false);
        }
    }

    public function test_la_home_no_carga_librerias_que_no_usa(): void
    {
        $home = $this->get('/');
        $home->assertOk();
        $home->assertDontSee('fotorama.js', false);
        $home->assertDontSee('select2.min.js', false);
        $home->assertDontSee('sdk.mercadopago.com', false);
        $home->assertDontSee('loadingio/ldbutton', false);
        $home->assertDontSee('family=DM+Sans', false);
        // El visor de fotos y la lupa sobreviven a cada visita.
        $home->assertSee('id="product-gallery" class="foto-galeria" data-turbo-permanent', false);
        $home->assertSee('<div id="pbmh-zoom" data-turbo-permanent', false);
    }

    public function test_la_zona_de_clientes_tambien_navega_sin_recargar(): void
    {
        $id = DB::connection('mysql_legacy')->table('users')->where('username', 'demo')->value('id');
        if ($id === null) {
            $this->markTestSkipped('Falta el usuario demo: php artisan db:seed --class=BmhDemoSeeder');
        }
        $cliente = User::query()->findOrFail($id);

        $carrito = $this->actingAs($cliente)->get(route('carrito'));
        $carrito->assertOk();
        $carrito->assertDontSee('name="turbo-visit-control"', false);
        $carrito->assertSee('<meta name="turbo-cache-control" content="no-cache">', false);
    }
}
