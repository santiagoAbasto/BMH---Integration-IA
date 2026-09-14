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
        $campos = [...Apariencia::camposHeader(), ...Apariencia::camposLogo()];

        return array_merge(array_intersect_key(Apariencia::defaults(), array_flip($campos)), $cambios);
    }

    // ------------------------------------------------------------ de fábrica

    public function test_la_migracion_deja_los_colores_que_ya_tenia_el_sitio(): void
    {
        $apariencia = Apariencia::actual();

        foreach (Apariencia::defaults() as $campo => $valor) {
            $this->assertSame($valor, $apariencia->{$campo}, "El valor de fábrica de {$campo} no coincide con la migración.");
        }
    }

    public function test_si_falta_la_tabla_el_sitio_usa_los_valores_de_fabrica(): void
    {
        // Se subieron los archivos y todavía no se corrió la migración.
        Schema::drop('apariencia');
        Cache::flush();

        $this->assertSame('#0098DA', Apariencia::actual()->header_transparente_scroll_fondo);
    }

    public function test_una_tabla_con_el_esquema_viejo_se_actualiza_sin_perder_lo_guardado(): void
    {
        // Quien corrió la versión anterior de la migración de creación tiene
        // esta tabla, y `migrate` no la repite: el admin y el front leían
        // columnas inexistentes y mostraban siempre los valores de fábrica.
        Schema::drop('apariencia');
        Schema::create('apariencia', function (Blueprint $t) {
            $t->id();
            $t->string('header_scroll_fondo', 7);
            $t->string('header_scroll_links', 7);
            $t->string('header_scroll_logo', 20);
            $t->string('header_mobile_boton_borde', 7);
            $t->string('footer_fondo', 7);
            $t->timestamps();
        });
        DB::table('apariencia')->insert([
            'header_scroll_fondo' => '#1f2a37', 'header_scroll_links' => '#FFD166',
            'header_scroll_logo' => Apariencia::LOGO_BLANCO, 'header_mobile_boton_borde' => '#ABCDEF',
            'footer_fondo' => '#111827',
        ]);
        Cache::flush();

        $this->artisan('migrate', ['--path' => 'database/migrations/2026_09_14_000001_actualizar_apariencia_a_estados_del_header.php'])->assertSuccessful();

        $this->assertFalse(Schema::hasColumn('apariencia', 'header_scroll_fondo'));
        $this->assertFalse(Schema::hasTable('apariencia_anterior'));

        $fila = Apariencia::actual();
        // El scroll valía para todas las páginas: pasa a los dos estados con scroll.
        $this->assertSame('#1F2A37', $fila->header_transparente_scroll_fondo);
        $this->assertSame('#1F2A37', $fila->header_blanco_scroll_fondo);
        $this->assertSame('#FFD166', $fila->header_blanco_scroll_links_hover);
        $this->assertSame(Apariencia::LOGO_BLANCO, $fila->header_blanco_scroll_logo);
        $this->assertSame('#ABCDEF', $fila->header_celular_boton);
        $this->assertSame('#111827', $fila->footer_fondo);
        // Lo que no existía queda de fábrica.
        $this->assertSame('#FFFFFF', $fila->header_blanco_reposo_fondo);
    }

    public function test_la_actualizacion_no_toca_una_tabla_que_ya_tiene_el_esquema_nuevo(): void
    {
        Apariencia::query()->first()->update(['header_blanco_reposo_fondo' => '#123456']);

        $this->artisan('migrate', ['--path' => 'database/migrations/2026_09_14_000001_actualizar_apariencia_a_estados_del_header.php'])->assertSuccessful();

        $this->assertSame('#123456', Apariencia::query()->first()->header_blanco_reposo_fondo);
    }

    public function test_los_cinco_estados_del_header_tienen_su_juego_completo_de_colores(): void
    {
        $esperados = ['transparente_reposo', 'transparente_scroll', 'blanco_reposo', 'blanco_scroll', 'celular'];
        $this->assertSame($esperados, array_keys(Apariencia::SETS));

        foreach (Apariencia::SETS as $set => $config) {
            $colores = Apariencia::coloresDe($set);

            // Cada set trae links y botón con sus hovers; sólo la Home en reposo
            // no puede tener fondo, porque ahí se ve la foto de portada.
            $this->assertContains('links_hover', $colores, "Falta el hover de los links en {$set}.");
            $this->assertContains('boton', $colores, "Falta el color del botón en {$set}.");
            $this->assertContains('boton_relleno', $colores, "Falta el relleno del botón en {$set}.");
            $this->assertContains('boton_hover_texto', $colores, "Falta el hover del botón en {$set}.");
            $this->assertSame($set !== 'transparente_reposo', in_array('fondo', $colores, true), "El fondo de {$set} no es el esperado.");

            foreach ([...$colores, 'logo'] as $color) {
                $this->assertArrayHasKey(Apariencia::campo($set, $color), Apariencia::defaults());
            }
        }
    }

    // -------------------------------------------------------------- pantallas

    public function test_el_editor_del_header_se_muestra(): void
    {
        $respuesta = $this->actingAs($this->admin, 'admin')->get('/dashboard/extras/header')->assertOk();

        // Los tres selectores, cada uno con sus campos y su vista previa.
        foreach (Apariencia::MODOS as $modo => $rotulo) {
            $respuesta->assertSee($rotulo)->assertSee('data-modo-panel="'.$modo.'"', false);
        }

        $respuesta
            ->assertSee('name="header_transparente_reposo_links_hover"', false)
            ->assertSee('name="header_blanco_reposo_fondo"', false)
            ->assertSee('name="header_blanco_scroll_boton_relleno"', false)
            ->assertSee('name="header_celular_logo"', false)
            ->assertSee('(011) 4482-2609');

        // La Home en reposo no ofrece color de fondo: ahí se ve la portada.
        $respuesta->assertDontSee('name="header_transparente_reposo_fondo"', false);
    }

    public function test_el_editor_del_footer_se_muestra(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get('/dashboard/extras/footer')
            ->assertOk()
            ->assertSee('name="footer_texto_hover"', false)
            ->assertSee('name="footer_derechos_fondo"', false)
            ->assertSee('name="footer_derechos_texto"', false)
            ->assertSee('bmh@example.com');
    }

    public function test_un_visitante_no_puede_editar(): void
    {
        $this->put('/dashboard/extras/header', $this->headerValido(['header_blanco_reposo_fondo' => '#111111']))
            ->assertRedirect('/login/admin');

        $this->assertSame('#FFFFFF', Apariencia::query()->first()->header_blanco_reposo_fondo);
    }

    // ---------------------------------------------------------------- colores

    public function test_guarda_los_colores_del_header_en_mayusculas(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put('/dashboard/extras/header', $this->headerValido([
                'header_blanco_reposo_fondo' => '#1f2a37',
                'header_celular_links' => '#ffd166',
                'header_transparente_scroll_logo' => Apariencia::LOGO_BLANCO,
            ]))
            ->assertRedirect('/dashboard/extras/header')
            ->assertSessionHas('success');

        $fila = Apariencia::query()->first();
        $this->assertSame('#1F2A37', $fila->header_blanco_reposo_fondo);
        $this->assertSame('#FFD166', $fila->header_celular_links);
        $this->assertSame(Apariencia::LOGO_BLANCO, $fila->header_transparente_scroll_logo);
    }

    public function test_cada_estado_guarda_sus_colores_sin_pisar_a_los_otros(): void
    {
        // El caso que motivó separar los estados: el header de las páginas
        // internas se pinta oscuro sin tocar el de la Home ni el del celular.
        $this->actingAs($this->admin, 'admin')
            ->put('/dashboard/extras/header', $this->headerValido([
                'header_blanco_reposo_fondo' => '#0B1F33',
                'header_blanco_reposo_links' => '#FFFFFF',
                'header_blanco_reposo_boton' => '#FFFFFF',
                'header_blanco_reposo_logo' => Apariencia::LOGO_TRANSPARENTE,
            ]))
            ->assertSessionHasNoErrors();

        $fila = Apariencia::query()->first();
        $this->assertSame('#0B1F33', $fila->header_blanco_reposo_fondo);
        $this->assertSame(Apariencia::LOGO_TRANSPARENTE, $fila->header_blanco_reposo_logo);

        // Los otros cuatro estados siguen como estaban.
        $this->assertSame('#FFFFFF', $fila->header_transparente_reposo_links);
        $this->assertSame('#0098DA', $fila->header_transparente_scroll_fondo);
        $this->assertSame('#0098DA', $fila->header_blanco_scroll_fondo);
        $this->assertSame('#FFFFFF', $fila->header_celular_fondo);
        $this->assertSame(Apariencia::LOGO_BLANCO, $fila->header_celular_logo);
    }

    public function test_rechaza_un_color_invalido_sin_guardar_nada(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put('/dashboard/extras/header', $this->headerValido([
                'header_blanco_scroll_fondo' => '#222222',
                'header_blanco_scroll_links' => 'rojo',
            ]))
            ->assertSessionHasErrors('header_blanco_scroll_links');

        $this->assertSame('#0098DA', Apariencia::query()->first()->header_blanco_scroll_fondo);
    }

    public function test_rechaza_un_logo_elegido_que_no_existe(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put('/dashboard/extras/header', $this->headerValido(['header_celular_logo' => 'otro']))
            ->assertSessionHasErrors('header_celular_logo');
    }

    public function test_guarda_el_footer(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put('/dashboard/extras/footer', [
                'footer_fondo' => '#111827', 'footer_texto' => '#e5e7eb', 'footer_texto_hover' => '#0098da',
                'footer_derechos_fondo' => '#0b1f33', 'footer_derechos_texto' => '#ffd166',
            ])
            ->assertRedirect('/dashboard/extras/footer');

        $fila = Apariencia::query()->first();
        $this->assertSame('#111827', $fila->footer_fondo);
        $this->assertSame('#E5E7EB', $fila->footer_texto);
        $this->assertSame('#0B1F33', $fila->footer_derechos_fondo);
        $this->assertSame('#FFD166', $fila->footer_derechos_texto);
        // Guardar el footer no toca el header.
        $this->assertSame('#0098DA', $fila->header_blanco_scroll_fondo);
    }

    public function test_guardar_invalida_el_cache_que_lee_el_front(): void
    {
        $this->assertSame('#0098DA', Apariencia::actual()->header_transparente_scroll_fondo);

        $this->actingAs($this->admin, 'admin')
            ->put('/dashboard/extras/header', $this->headerValido(['header_transparente_scroll_fondo' => '#333333']));

        $this->assertSame('#333333', Apariencia::actual()->header_transparente_scroll_fondo);
    }

    // ------------------------------------------------------------------ front

    public function test_el_front_publica_los_colores_como_variables_css(): void
    {
        Apariencia::query()->first()->update([
            'header_celular_fondo' => '#0B1F33',
            'header_blanco_reposo_fondo' => '#123456',
            'footer_texto' => '#E5E7EB',
        ]);

        $css = view('layouts.partials.apariencia', ['apariencia' => Apariencia::actual()])->render();

        $this->assertStringContainsString('--ap-cel-fondo: #0B1F33', $css);
        $this->assertStringContainsString('--ap-br-fondo: #123456', $css);
        $this->assertStringContainsString('--ap-f-texto: #E5E7EB', $css);
        $this->assertStringContainsString('--ap-f-derechos-fondo: #241F21', $css);
        $this->assertStringContainsString('#site-footer .footer-derechos', $css);
    }

    public function test_el_front_pinta_los_cuatro_estados_de_computadora_por_separado(): void
    {
        $css = view('layouts.partials.apariencia', ['apariencia' => Apariencia::actual()])->render();

        // Home y páginas internas, en reposo y con scroll, no comparten reglas.
        $this->assertStringContainsString('#site-header.home:not(.scrolled)', $css);
        $this->assertStringContainsString('#site-header.home.scrolled', $css);
        $this->assertStringContainsString('#site-header:not(.home):not(.scrolled)', $css);
        $this->assertStringContainsString('#site-header:not(.home).scrolled', $css);

        // El fondo de las páginas internas en reposo ya es editable.
        $this->assertStringContainsString('#site-header:not(.home):not(.scrolled) .navbar { background-color: var(--ap-br-fondo)', $css);

        // La Home en reposo no pinta fondo: se ve la portada.
        $this->assertStringNotContainsString('#site-header.home:not(.scrolled) .navbar { background-color', $css);
    }

    // ----------------------------------------------------------------- logos

    public function test_cada_estado_elige_su_propio_logo(): void
    {
        Apariencia::query()->first()->update([
            'header_blanco_reposo_logo' => Apariencia::LOGO_TRANSPARENTE,
            'header_celular_logo' => Apariencia::LOGO_BLANCO,
        ]);
        $apariencia = Apariencia::actual();

        $this->assertSame(Apariencia::LOGO_TRANSPARENTE, $apariencia->logoDe('blanco_reposo'));
        $this->assertSame(Apariencia::LOGO_BLANCO, $apariencia->logoDe('celular'));

        // Un valor basura en la base no rompe el front: cae al de fábrica.
        Apariencia::query()->first()->update(['header_celular_logo' => 'otro']);
        Cache::flush();
        $this->assertSame(Apariencia::LOGO_BLANCO, Apariencia::actual()->logoDe('celular'));
    }

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
            ->put('/dashboard/extras/footer', array_intersect_key(Apariencia::defaults(), array_flip(Apariencia::CAMPOS_FOOTER)) + ['logo' => $this->png()])
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
