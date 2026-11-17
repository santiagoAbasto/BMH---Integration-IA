<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use App\Models\Anuncio;
use App\Models\Carrito;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Panel › Carrito: el anuncio (ahora una imagen) y el mensaje del pedido enviado.
 *
 * SQLite en memoria y public/ apuntando a un directorio temporal: las imágenes no tocan public/imagenes.
 */
final class AnuncioYMensajePedidoTest extends TestCase
{
    private string $publico;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite_memoria',
            'database.connections.sqlite_memoria' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);
        DB::purge('sqlite_memoria');
        Cache::flush();

        $this->publico = sys_get_temp_dir().'/bmh_publico_'.uniqid();
        File::ensureDirectoryExists($this->publico.'/imagenes');
        $this->app->usePublicPath($this->publico);
        $this->withoutVite();

        Schema::create('admins', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('username')->nullable();
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->string('rol')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });
        Schema::create('imagenes', function (Blueprint $t) {
            $t->id();
            $t->string('path');
            $t->string('sector')->nullable();
            $t->timestamps();
        });
        Schema::create('pedidos', fn (Blueprint $t) => $t->id() && $t->timestamps());
        Schema::create('anuncios', function (Blueprint $t) {
            $t->id();
            $t->longText('contenido')->nullable();
            $t->boolean('mostrar')->default(false);
            $t->timestamps();
        });
        Schema::create('carrito_informacion', function (Blueprint $t) {
            $t->id();
            foreach (['info', 'pedido', 'pedido_titulo', 'info_efectivo', 'info_mp', 'info_empresa', 'info_retiro', 'info_convenir', 'descuento_efectivo'] as $c) {
                $t->text($c)->nullable();
            }
            $t->timestamps();
        });
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->publico);
        parent::tearDown();
    }

    private function admin(): Admin
    {
        return Admin::query()->forceCreate(['name' => 'Admin', 'username' => 'admin', 'rol' => 'administrador', 'password' => 'x']);
    }

    private function migrarAnuncio(): void
    {
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_11_17_000008_anuncio_como_imagen.php'])->assertSuccessful();
    }

    // --- Anuncio ---

    public function test_la_migracion_saca_la_imagen_base64_del_contenido_a_un_archivo(): void
    {
        $png = UploadedFile::fake()->image('oferta.png', 700, 500);
        $base64 = base64_encode((string) file_get_contents($png->getRealPath()));
        DB::table('anuncios')->insert(['id' => 1, 'mostrar' => true, 'contenido' => '<p style="text-align: center;"><img src="data:image/png;base64,'.$base64.'" data-filename="oferta compresor.png" style="width: 100%;"><br></p>']);

        $this->migrarAnuncio();

        $anuncio = Anuncio::query()->findOrFail(1);
        $this->assertMatchesRegularExpression('/^anuncio-[a-f0-9]{12}\.png$/', $anuncio->imagen);
        $this->assertFileExists($this->publico.'/imagenes/'.$anuncio->imagen);
        $this->assertSame([700, 500, 'Oferta compresor'], [$anuncio->ancho, $anuncio->alto, $anuncio->texto_alternativo]);
        // El contenido viejo queda como estaba.
        $this->assertStringContainsString('base64', (string) $anuncio->contenido);
    }

    public function test_el_admin_carga_una_imagen_y_reemplaza_la_anterior(): void
    {
        $this->migrarAnuncio();
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->get(route('dashboard.anuncio'))->assertOk()
            ->assertSee('Mostrar a los clientes')
            ->assertSee('Cargá una imagen para poder mostrar el anuncio.');

        $this->actingAs($admin, 'admin')->put(route('anuncio.update'), [
            'imagen' => UploadedFile::fake()->image('anuncio.jpg', 1400, 1000),
            'texto_alternativo' => '20% en compresores',
        ])->assertRedirect(route('dashboard.anuncio'));

        $primera = Anuncio::query()->findOrFail(1);
        $this->assertSame([1400, 1000, '20% en compresores'], [$primera->ancho, $primera->alto, $primera->texto_alternativo]);
        $this->assertFileExists($this->publico.'/imagenes/'.$primera->imagen);

        $this->actingAs($admin, 'admin')->put(route('anuncio.update'), ['imagen' => UploadedFile::fake()->image('otra.png', 800, 1000)])
            ->assertRedirect(route('dashboard.anuncio'));

        $segunda = Anuncio::query()->findOrFail(1);
        $this->assertNotSame($primera->imagen, $segunda->imagen);
        $this->assertFileDoesNotExist($this->publico.'/imagenes/'.$primera->imagen);
        $this->assertFileExists($this->publico.'/imagenes/'.$segunda->imagen);
    }

    public function test_la_imagen_se_valida(): void
    {
        $this->migrarAnuncio();
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->put(route('anuncio.update'), ['imagen' => UploadedFile::fake()->image('chica.png', 300, 200)])
            ->assertSessionHasErrors('imagen');
        $this->actingAs($admin, 'admin')->put(route('anuncio.update'), ['imagen' => UploadedFile::fake()->create('virus.svg', 10, 'image/svg+xml')])
            ->assertSessionHasErrors('imagen');
        $this->actingAs($admin, 'admin')->put(route('anuncio.update'), [])->assertSessionHasErrors('imagen');

        $this->assertNull(Anuncio::query()->findOrFail(1)->imagen);
    }

    public function test_mostrar_y_ocultar_es_explicito_y_pide_imagen(): void
    {
        $this->migrarAnuncio();
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->postJson(route('anuncio.mostrar'), ['mostrar' => true])->assertStatus(422);

        $this->actingAs($admin, 'admin')->put(route('anuncio.update'), ['imagen' => UploadedFile::fake()->image('a.png', 700, 500)]);

        // Dos veces «mostrar» deja el anuncio visible (antes cada llamada lo invertía).
        $this->actingAs($admin, 'admin')->postJson(route('anuncio.mostrar'), ['mostrar' => true])->assertOk()->assertJson(['mostrar' => true]);
        $this->actingAs($admin, 'admin')->postJson(route('anuncio.mostrar'), ['mostrar' => true])->assertOk()->assertJson(['mostrar' => true]);
        $this->assertTrue(Anuncio::query()->findOrFail(1)->visible());

        $this->actingAs($admin, 'admin')->postJson(route('anuncio.mostrar'), ['mostrar' => false])->assertOk()->assertJson(['mostrar' => false]);
        $this->assertFalse(Anuncio::query()->findOrFail(1)->visible());
    }

    public function test_sin_sesion_del_panel_no_se_toca_el_anuncio(): void
    {
        $this->migrarAnuncio();

        $this->put(route('anuncio.update'), ['imagen' => UploadedFile::fake()->image('a.png', 700, 500)])->assertRedirect();
        $this->post(route('anuncio.mostrar'), ['mostrar' => true])->assertRedirect();
        $this->assertNull(Anuncio::query()->find(1)?->imagen);
        $this->assertCount(0, File::files($this->publico.'/imagenes'));
    }

    // --- Mensaje del pedido enviado ---

    public function test_el_mensaje_del_pedido_se_edita_como_texto_y_no_borra_lo_demas(): void
    {
        DB::table('carrito_informacion')->insert([
            'id' => 1, 'pedido_titulo' => '¡Tu pedido se ha enviado!', 'pedido' => '<p>Gracias&nbsp;por elegirnos.</p>',
            'info_retiro' => 'Retiro por Av. Eva Perón', 'info_empresa' => 'Reparto Iptsa', 'descuento_efectivo' => '5',
        ]);
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->get(route('dashboard.informacion'))->assertOk()
            ->assertSee('Mensaje del pedido enviado')
            ->assertSee('Gracias por elegirnos.</textarea>', false);

        $this->actingAs($admin, 'admin')->put(route('carrito.informacion.update'), [
            'pedido_titulo' => ' ¡Recibimos tu pedido! ',
            'pedido' => "Gracias por elegirnos.\nTe escribimos hoy.\n\n<script>alert(1)</script> Pagás al retirar.",
        ])->assertRedirect(route('dashboard.informacion'));

        $info = Carrito::query()->findOrFail(1);
        $this->assertSame('¡Recibimos tu pedido!', $info->pedido_titulo);
        $this->assertSame('<p>Gracias por elegirnos.<br>'."\n".'Te escribimos hoy.</p><p>&lt;script&gt;alert(1)&lt;/script&gt; Pagás al retirar.</p>', $info->pedido);
        // Lo que no está en el formulario no se pisa.
        $this->assertSame(['Retiro por Av. Eva Perón', 'Reparto Iptsa', '5'], [$info->info_retiro, $info->info_empresa, $info->descuento_efectivo]);
    }

    public function test_el_mensaje_del_pedido_es_obligatorio(): void
    {
        $this->actingAs($this->admin(), 'admin')->put(route('carrito.informacion.update'), ['pedido_titulo' => '', 'pedido' => ''])
            ->assertSessionHasErrors(['pedido_titulo', 'pedido']);
    }
}
