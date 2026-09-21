<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use App\Models\Apariencia;
use App\Models\Contacto;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Extras del admin: barra de contacto de arriba del header.
 *
 * Hermético como AparienciaTest: SQLite en memoria con el mínimo esquema que
 * necesita el layout del backend, más las migraciones reales de `apariencia`.
 */
final class BarraSuperiorTest extends TestCase
{
    private const URL = '/dashboard/extras/barra-superior';

    private Admin $admin;

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
            $t->timestamps();
        });
        Schema::create('pedidos', fn (Blueprint $t) => $t->id() && $t->timestamps());
        // Igual que en producción: `iframe` no admite null.
        Schema::create('contacto', function (Blueprint $t) {
            $t->id();
            $t->string('direccion')->nullable();
            $t->text('iframe');
            $t->string('tel')->nullable();
            $t->string('mail')->nullable();
            $t->string('instagram')->nullable();
            $t->string('tiktok')->nullable();
            $t->string('whatsapp')->nullable();
            $t->string('facebook')->nullable();
            $t->timestamps();
        });

        $this->artisan('migrate', ['--path' => 'database/migrations/2026_09_11_000001_create_apariencia_table.php'])->assertSuccessful();
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_09_21_000001_agregar_barra_superior_a_apariencia.php'])->assertSuccessful();

        DB::table('imagenes')->insert([
            ['sector' => 'logo', 'path' => 'logo.png'],
            ['sector' => 'logo2', 'path' => 'logo.png'],
        ]);
        DB::table('contacto')->insert([
            'id' => 1,
            'direccion' => 'Constantino Gaito 2874',
            'iframe' => '<iframe></iframe>',
            'tel' => '(011) 4482-2609',
            'mail' => 'bobinajesbmh@gmail.com',
            'instagram' => 'https://www.instagram.com/bmh',
            'tiktok' => 'https://www.tiktok.com/@bmh',
            'whatsapp' => '+54 9 11 5555-5555',
            'facebook' => 'https://www.facebook.com/bmh',
        ]);

        $this->admin = Admin::query()->create([
            'name' => 'Tester', 'username' => 'tester', 'email' => 't@example.com',
            'password' => 'x', 'rol' => 'administrador',
        ]);
    }

    /** @return array<string, string|null> */
    private function valido(array $cambios = []): array
    {
        return array_merge(Apariencia::DEFAULTS_BARRA, [
            'tel' => '(011) 4482-2609',
            'mail' => 'bobinajesbmh@gmail.com',
            'tiktok' => 'https://www.tiktok.com/@bmh',
            'instagram' => 'https://www.instagram.com/bmh',
            'facebook' => 'https://www.facebook.com/bmh',
        ], $cambios);
    }

    private function barraDelSitio(): string
    {
        return view('layouts.partials.barra-superior', ['contacto' => Contacto::actual()])->render();
    }

    // ------------------------------------------------------------ de fábrica

    public function test_la_migracion_deja_los_colores_que_ya_tenia_la_barra(): void
    {
        $apariencia = Apariencia::actual();

        $this->assertSame('#0098DA', $apariencia->barra_fondo);
        $this->assertSame('#FFFFFF', $apariencia->barra_texto);
        $this->assertSame('#FFFFFF', $apariencia->barra_hover);
    }

    public function test_la_migracion_no_hace_nada_si_las_columnas_ya_existen(): void
    {
        Apariencia::query()->first()->update(['barra_fondo' => '#123456']);

        $this->artisan('migrate', ['--path' => 'database/migrations/2026_09_21_000001_agregar_barra_superior_a_apariencia.php'])->assertSuccessful();

        $this->assertSame('#123456', Apariencia::query()->first()->barra_fondo);
    }

    // -------------------------------------------------------------- pantalla

    public function test_el_editor_se_muestra_con_los_datos_actuales_y_la_vista_previa(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(self::URL)
            ->assertOk()
            ->assertSee('value="(011) 4482-2609"', false)
            ->assertSee('value="bobinajesbmh@gmail.com"', false)
            ->assertSee('value="https://www.instagram.com/bmh"', false)
            ->assertSee('name="barra_fondo"', false)
            ->assertSee('name="barra_hover"', false)
            ->assertSee('a tamaño real')
            // Avisa que los datos son compartidos con Contacto.
            ->assertSee('también cambian en el footer')
            ->assertSee('>Barra superior</a>', false);
    }

    public function test_un_visitante_no_puede_editarla(): void
    {
        $this->put(self::URL, $this->valido(['tel' => '0800-000']))->assertRedirect('/login/admin');

        $this->assertSame('(011) 4482-2609', Contacto::actual()->tel);
    }

    // ---------------------------------------------------------------- guardar

    public function test_guarda_colores_en_apariencia_y_datos_en_contacto(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(self::URL, $this->valido([
                'barra_fondo' => '#1f2a37',
                'barra_hover' => '#ffd166',
                'tel' => '  0800-222-BMH  ',
                'mail' => 'ventas@bmh.com.ar',
            ]))
            ->assertRedirect(self::URL)
            ->assertSessionHas('success');

        $apariencia = Apariencia::actual();
        $this->assertSame('#1F2A37', $apariencia->barra_fondo);
        $this->assertSame('#FFD166', $apariencia->barra_hover);

        $contacto = Contacto::actual();
        $this->assertSame('0800-222-BMH', $contacto->tel);
        $this->assertSame('ventas@bmh.com.ar', $contacto->mail);
        // Lo que no se edita acá queda intacto.
        $this->assertSame('+54 9 11 5555-5555', $contacto->whatsapp);
        $this->assertSame('Constantino Gaito 2874', $contacto->direccion);
    }

    public function test_un_link_sin_protocolo_se_guarda_con_https(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(self::URL, $this->valido(['instagram' => 'instagram.com/bmh.oficial']))
            ->assertSessionHasNoErrors();

        $this->assertSame('https://instagram.com/bmh.oficial', Contacto::actual()->instagram);
    }

    public function test_vaciar_un_dato_lo_saca_de_la_barra(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(self::URL, $this->valido(['tiktok' => '', 'mail' => '']))
            ->assertSessionHasNoErrors();

        $contacto = Contacto::actual();
        $this->assertNull($contacto->tiktok);
        $this->assertNull($contacto->mail);

        $html = $this->barraDelSitio();
        $this->assertStringNotContainsString('tiktok', $html);
        $this->assertStringNotContainsString('mailto:', $html);
        $this->assertStringContainsString('instagram.com/bmh', $html);
    }

    /** @return array<string, array{string, string}> */
    public static function datosInvalidos(): array
    {
        return [
            'mail' => ['mail', 'no-es-un-mail'],
            'link' => ['facebook', 'https://'],
            'teléfono sin números' => ['tel', 'llamanos'],
            'color' => ['barra_texto', 'blanco'],
        ];
    }

    /** @dataProvider datosInvalidos */
    public function test_rechaza_datos_invalidos_sin_guardar_nada(string $campo, string $valor): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put(self::URL, $this->valido([$campo => $valor, 'barra_fondo' => '#000000']))
            ->assertSessionHasErrors($campo);

        // Ni los colores ni los datos cambian: se guarda todo o nada.
        $this->assertSame('#0098DA', Apariencia::actual()->barra_fondo);
        $this->assertSame('(011) 4482-2609', Contacto::actual()->tel);
    }

    // ------------------------------------------------------------------ front

    public function test_la_barra_del_sitio_usa_los_colores_y_los_datos_guardados(): void
    {
        $html = $this->barraDelSitio();

        $this->assertStringContainsString('id="site-topbar"', $html);
        // El teléfono llama: antes el link estaba vacío.
        $this->assertStringContainsString('href="tel:01144822609"', $html);
        $this->assertStringContainsString('href="mailto:bobinajesbmh@gmail.com"', $html);
        $this->assertStringContainsString('href="https://www.facebook.com/bmh"', $html);

        $css = view('layouts.partials.apariencia', ['apariencia' => Apariencia::actual()])->render();
        $this->assertStringContainsString('--ap-tb-fondo: #0098DA', $css);
        $this->assertStringContainsString('#site-topbar .barra-superior { background-color: var(--ap-tb-fondo); }', $css);
    }

    public function test_el_link_del_telefono_conserva_el_prefijo_internacional(): void
    {
        $contacto = new Contacto();

        $contacto->tel = '+54 11 4482-2609';
        $this->assertSame('tel:+541144822609', $contacto->telHref());

        $contacto->tel = '';
        $this->assertNull($contacto->telHref());
    }
}
