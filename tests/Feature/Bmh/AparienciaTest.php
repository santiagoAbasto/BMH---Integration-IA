<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use App\Models\Apariencia;
use App\Models\Imagen;
use App\Services\LogosSitio;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Extras del admin: logos y colores del header y el footer.
 *
 * Hermético como CaracteristicaDashboardTest: SQLite en memoria con el mínimo
 * esquema que necesita el layout del backend, más la migración real de
 * `apariencia`. Los logos se escriben en un directorio temporal, no en public/.
 */
final class AparienciaTest extends TestCase
{
    /** PNG de 1×1: contenido real para que la validación `mimes` lo reconozca. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private Admin $admin;

    private string $directorio;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite_memoria',
            'database.connections.sqlite_memoria' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);
        DB::purge('sqlite_memoria');
        Cache::flush();

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
            $t->string('tipo')->default('imagen');
            $t->string('orden')->default('aa');
            $t->string('posicion')->nullable();
            $t->timestamps();
        });
        Schema::create('pedidos', fn (Blueprint $t) => $t->id() && $t->timestamps());
        Schema::create('contacto', function (Blueprint $t) {
            $t->id();
            $t->string('direccion')->nullable();
            $t->string('tel')->nullable();
            $t->string('mail')->nullable();
            $t->string('whatsapp')->nullable();
            $t->timestamps();
        });

        $this->artisan('migrate', ['--path' => 'database/migrations/2026_09_11_000001_create_apariencia_table.php'])->assertSuccessful();

        $this->directorio = sys_get_temp_dir().'/bmh_logos_'.uniqid();
        File::ensureDirectoryExists($this->directorio);
        File::put($this->directorio.'/actual.png', base64_decode(self::PNG));
        $this->app->instance(LogosSitio::class, new LogosSitio($this->directorio));

        // `logo` (header transparente, favicon y login) y `logo2` (footer y admin)
        // comparten archivo, como pasa hoy en producción.
        DB::table('imagenes')->insert([
            ['sector' => 'logo', 'path' => 'actual.png'],
            ['sector' => 'logo2', 'path' => 'actual.png'],
        ]);
        DB::table('contacto')->insert(['id' => 1, 'tel' => '(011) 4482-2609', 'mail' => 'bmh@example.com']);

        $this->admin = Admin::query()->create([
            'name' => 'Tester', 'username' => 'tester', 'email' => 't@example.com',
            'password' => 'x', 'rol' => 'administrador',
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directorio);
        parent::tearDown();
    }

    private function png(string $nombre = 'logo.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nombre, base64_decode(self::PNG));
    }

    /** @return array<string, string> */
    private function headerValido(array $cambios = []): array
    {
        return array_merge(
            array_intersect_key(Apariencia::DEFAULTS, array_flip([...Apariencia::CAMPOS_HEADER, 'header_scroll_logo', 'header_mobile_logo'])),
            $cambios
        );
    }

    // ---------------------------------------------------------- de fábrica

    public function test_la_migracion_deja_los_colores_que_ya_tenia_el_sitio(): void
    {
        $apariencia = Apariencia::actual();

        foreach (Apariencia::DEFAULTS as $campo => $valor) {
            $this->assertSame($valor, $apariencia->{$campo}, "El valor de fábrica de {$campo} no coincide con la migración.");
        }
    }

    public function test_si_falta_la_tabla_el_sitio_usa_los_valores_de_fabrica(): void
    {
        // Se subieron los archivos y todavía no se corrió la migración.
        Schema::drop('apariencia');
        Cache::flush();

        $this->assertSame('#0098DA', Apariencia::actual()->header_scroll_fondo);
    }

    // ------------------------------------------------------------ pantallas

    public function test_el_editor_del_header_se_muestra(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get('/dashboard/extras/header')
            ->assertOk()
            ->assertSee('Al hacer scroll · computadora')
            ->assertSee('name="header_mobile_links"', false)
            ->assertSee('data-tab="celular"', false)
            ->assertSee('(011) 4482-2609');
    }

    public function test_el_editor_del_footer_se_muestra(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get('/dashboard/extras/footer')
            ->assertOk()
            ->assertSee('name="footer_texto_hover"', false)
            ->assertSee('bmh@example.com');
    }

    public function test_un_visitante_no_puede_editar(): void
    {
        $this->put('/dashboard/extras/header', $this->headerValido(['header_scroll_fondo' => '#111111']))
            ->assertRedirect('/login/admin');

        $this->assertSame('#0098DA', Apariencia::query()->first()->header_scroll_fondo);
    }

    // -------------------------------------------------------------- colores

    public function test_guarda_los_colores_del_header_en_mayusculas(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put('/dashboard/extras/header', $this->headerValido([
                'header_scroll_fondo' => '#1f2a37',
                'header_mobile_links' => '#ffd166',
                'header_scroll_logo' => Apariencia::LOGO_BLANCO,
            ]))
            ->assertRedirect('/dashboard/extras/header')
            ->assertSessionHas('success');

        $fila = Apariencia::query()->first();
        $this->assertSame('#1F2A37', $fila->header_scroll_fondo);
        $this->assertSame('#FFD166', $fila->header_mobile_links);
        $this->assertSame(Apariencia::LOGO_BLANCO, $fila->header_scroll_logo);
    }

    public function test_rechaza_un_color_invalido_sin_guardar_nada(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put('/dashboard/extras/header', $this->headerValido([
                'header_scroll_fondo' => '#222222',
                'header_scroll_links' => 'rojo',
            ]))
            ->assertSessionHasErrors('header_scroll_links');

        $this->assertSame('#0098DA', Apariencia::query()->first()->header_scroll_fondo);
    }

    public function test_rechaza_un_logo_elegido_que_no_existe(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put('/dashboard/extras/header', $this->headerValido(['header_mobile_logo' => 'otro']))
            ->assertSessionHasErrors('header_mobile_logo');
    }

    public function test_guarda_el_footer(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put('/dashboard/extras/footer', ['footer_fondo' => '#111827', 'footer_texto' => '#e5e7eb', 'footer_texto_hover' => '#0098da'])
            ->assertRedirect('/dashboard/extras/footer');

        $fila = Apariencia::query()->first();
        $this->assertSame('#111827', $fila->footer_fondo);
        $this->assertSame('#E5E7EB', $fila->footer_texto);
        // Guardar el footer no toca el header.
        $this->assertSame('#0098DA', $fila->header_scroll_fondo);
    }

    public function test_guardar_invalida_el_cache_que_lee_el_front(): void
    {
        $this->assertSame('#0098DA', Apariencia::actual()->header_scroll_fondo);

        $this->actingAs($this->admin, 'admin')
            ->put('/dashboard/extras/header', $this->headerValido(['header_scroll_fondo' => '#333333']));

        $this->assertSame('#333333', Apariencia::actual()->header_scroll_fondo);
    }

    public function test_el_front_publica_los_colores_como_variables_css(): void
    {
        Apariencia::query()->first()->update(['header_mobile_fondo' => '#0B1F33', 'footer_texto' => '#E5E7EB']);

        $css = view('layouts.partials.apariencia', ['apariencia' => Apariencia::actual()])->render();

        $this->assertStringContainsString('--ap-hm-fondo: #0B1F33', $css);
        $this->assertStringContainsString('--ap-f-texto: #E5E7EB', $css);
        $this->assertStringContainsString('#site-header.scrolled', $css);
    }

    // ---------------------------------------------------------------- logos

    public function test_el_logo_para_fondo_blanco_usa_el_transparente_mientras_no_se_suba_otro(): void
    {
        $logos = $this->app->make(LogosSitio::class);

        $this->assertSame($logos->url(LogosSitio::HEADER_TRANSPARENTE), $logos->url(LogosSitio::HEADER_BLANCO));
        $this->assertFalse($logos->tienePropio(LogosSitio::HEADER_BLANCO));
    }

    public function test_subir_el_logo_blanco_no_borra_el_archivo_que_usan_otros(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put('/dashboard/extras/header', $this->headerValido() + ['logo_blanco' => $this->png()])
            ->assertSessionHasNoErrors();

        $blanco = Imagen::query()->where('sector', 'logo-header-blanco')->value('path');
        $this->assertNotNull($blanco);
        $this->assertFileExists($this->directorio.'/'.$blanco);
        $this->assertFileExists($this->directorio.'/actual.png', 'El archivo compartido por logo y logo2 no se puede borrar.');

        // Reemplazarlo de nuevo sí borra el anterior, que ya nadie usa.
        $this->actingAs($this->admin, 'admin')
            ->put('/dashboard/extras/header', $this->headerValido() + ['logo_blanco' => $this->png('otro.png')]);

        $this->assertFileDoesNotExist($this->directorio.'/'.$blanco);
        $this->assertFileExists($this->directorio.'/actual.png');
    }

    public function test_subir_el_logo_del_footer_mantiene_el_del_header(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put('/dashboard/extras/footer', array_intersect_key(Apariencia::DEFAULTS, array_flip(Apariencia::CAMPOS_FOOTER)) + ['logo' => $this->png()])
            ->assertSessionHasNoErrors();

        $this->assertNotSame('actual.png', Imagen::query()->where('sector', 'logo2')->value('path'));
        $this->assertSame('actual.png', Imagen::query()->where('sector', 'logo')->value('path'));
        $this->assertFileExists($this->directorio.'/actual.png');
    }

    public function test_rechaza_un_logo_que_no_es_imagen(): void
    {
        // El fake de Laravel deduce el tipo por la extensión del nombre (en una
        // subida real Symfony lo detecta por contenido), así que el archivo
        // inválido tiene que llamarse como lo que es.
        $this->actingAs($this->admin, 'admin')
            ->put('/dashboard/extras/header', $this->headerValido() + [
                'logo_transparente' => UploadedFile::fake()->create('catalogo.pdf', 20, 'application/pdf'),
            ])
            ->assertSessionHasErrors('logo_transparente');

        $this->assertSame('actual.png', Imagen::query()->where('sector', 'logo')->value('path'));
    }
}
