<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use App\Models\Imagen;
use App\Models\Producto;
use App\Services\LogosSitio;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Extras del admin: imagen que muestran los productos sin portada.
 *
 * Hermético como AparienciaTest: SQLite en memoria con el mínimo esquema que
 * necesita el layout del backend, y las imágenes en un directorio temporal.
 */
final class ImagenPorDefectoTest extends TestCase
{
    private const URL = '/dashboard/extras/imagen-por-defecto';

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
            $t->integer('producto_id')->nullable();
            $t->string('posicion')->nullable();
            $t->timestamps();
        });
        Schema::create('productos', function (Blueprint $t) {
            $t->id();
            $t->string('codigo')->nullable();
            $t->string('nombre')->nullable();
            $t->string('marca')->nullable();
            $t->string('modelo')->nullable();
        });
        Schema::create('pedidos', fn (Blueprint $t) => $t->id() && $t->timestamps());
        Schema::create('contacto', function (Blueprint $t) {
            $t->id();
            $t->string('tel')->nullable();
            $t->string('mail')->nullable();
            $t->timestamps();
        });

        $this->directorio = sys_get_temp_dir().'/bmh_defecto_'.uniqid();
        File::ensureDirectoryExists($this->directorio);
        $this->app->instance(LogosSitio::class, new LogosSitio($this->directorio));

        DB::table('productos')->insert([
            ['id' => 1, 'codigo' => 'BMH-100', 'nombre' => 'Inducido sin foto'],
            ['id' => 2, 'codigo' => 'BMH-200', 'nombre' => 'Inducido con foto'],
            ['id' => 3, 'codigo' => 'BMH-300', 'nombre' => 'Otro sin foto'],
        ]);
        File::put($this->directorio.'/portada.png', 'x');
        DB::table('imagenes')->insert([
            ['sector' => 'producto', 'tipo' => 'portada', 'producto_id' => 2, 'path' => 'portada.png'],
            // El sidebar del admin lee el logo de `logo2`: en producción siempre existe.
            ['sector' => 'logo', 'tipo' => 'imagen', 'producto_id' => null, 'path' => 'logo.png'],
            ['sector' => 'logo2', 'tipo' => 'imagen', 'producto_id' => null, 'path' => 'logo.png'],
        ]);

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

    private function logos(): LogosSitio
    {
        return $this->app->make(LogosSitio::class);
    }

    private function subir(UploadedFile $archivo): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin, 'admin')->put(self::URL, ['imagen' => $archivo]);
    }

    // --------------------------------------------------------------- pantalla

    public function test_el_editor_se_muestra_con_la_imagen_de_bmh_y_la_vista_previa(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(self::URL)
            ->assertOk()
            ->assertSee('Imagen de BMH')
            ->assertSee('name="imagen"', false)
            // Dos de los tres productos no tienen portada.
            ->assertSee('<strong>2</strong>', false)
            // La vista previa usa un producto real sin foto.
            ->assertSee('BMH-100')
            ->assertSee('Ficha del producto')
            ->assertSee('>Imagen por defecto</a>', false)
            ->assertDontSee('<h2>Volver a la imagen de BMH</h2>', false);
    }

    public function test_un_visitante_no_puede_cambiarla(): void
    {
        $this->put(self::URL, ['imagen' => UploadedFile::fake()->image('defecto.png', 800, 600)])
            ->assertRedirect('/login/admin');

        $this->assertNull(Imagen::query()->where('sector', 'producto-defecto')->first());
    }

    // ------------------------------------------------------------------ subir

    public function test_sin_imagen_propia_los_productos_usan_la_de_bmh(): void
    {
        $this->assertSame(route('producto.placeholder'), Producto::imagenPredeterminadaUrl());
        $this->assertSame(route('producto.placeholder'), Producto::query()->find(1)->portadaUrl());
    }

    public function test_subir_una_imagen_la_usan_los_productos_sin_portada(): void
    {
        $this->subir(UploadedFile::fake()->image('defecto.png', 1200, 900))
            ->assertRedirect(self::URL)
            ->assertSessionHasNoErrors();

        $path = Imagen::query()->where('sector', 'producto-defecto')->value('path');
        $this->assertNotNull($path);
        $this->assertFileExists($this->directorio.'/'.$path);

        $url = asset('imagenes/'.$path);
        $this->assertSame($url, Producto::imagenPredeterminadaUrl());
        $this->assertSame($url, Producto::query()->find(1)->portadaUrl());
        // La de BMH sigue disponible para restaurarla.
        $this->assertSame(route('producto.placeholder'), Producto::imagenPredeterminadaBmhUrl());

        $this->actingAs($this->admin, 'admin')->get(self::URL)
            ->assertSee('Imagen propia')
            ->assertSee('<h2>Volver a la imagen de BMH</h2>', false);
    }

    public function test_reemplazarla_borra_el_archivo_anterior(): void
    {
        $this->subir(UploadedFile::fake()->image('primera.png', 800, 600));
        $primera = Imagen::query()->where('sector', 'producto-defecto')->value('path');

        $this->subir(UploadedFile::fake()->image('segunda.jpg', 800, 600));

        $this->assertSame(1, Imagen::query()->where('sector', 'producto-defecto')->count());
        $this->assertFileDoesNotExist($this->directorio.'/'.$primera);
    }

    /** @return array<string, array{UploadedFile, string}> */
    public static function archivosInvalidos(): array
    {
        return [
            'no es imagen' => [UploadedFile::fake()->create('catalogo.pdf', 20, 'application/pdf'), 'JPG, PNG o WEBP'],
            'svg' => [UploadedFile::fake()->create('logo.svg', 5, 'image/svg+xml'), 'JPG, PNG o WEBP'],
            'muy chica' => [UploadedFile::fake()->image('chica.png', 120, 120), 'al menos 300'],
            'muy pesada' => [UploadedFile::fake()->image('pesada.jpg', 800, 600)->size(6000), '5 MB'],
        ];
    }

    /** @dataProvider archivosInvalidos */
    public function test_rechaza_archivos_que_no_sirven_como_foto_de_catalogo(UploadedFile $archivo, string $mensaje): void
    {
        $this->subir($archivo)->assertSessionHasErrors('imagen');

        $this->assertStringContainsString($mensaje, session('errors')->first('imagen'));
        $this->assertNull(Imagen::query()->where('sector', 'producto-defecto')->first());
    }

    // -------------------------------------------------------------- restaurar

    public function test_restaurar_borra_la_imagen_propia_y_vuelve_a_la_de_bmh(): void
    {
        $this->subir(UploadedFile::fake()->image('defecto.png', 800, 600));
        $path = Imagen::query()->where('sector', 'producto-defecto')->value('path');

        $this->actingAs($this->admin, 'admin')
            ->delete(self::URL)
            ->assertRedirect(self::URL)
            ->assertSessionHas('success');

        $this->assertNull(Imagen::query()->where('sector', 'producto-defecto')->first());
        $this->assertFileDoesNotExist($this->directorio.'/'.$path);
        $this->assertSame(route('producto.placeholder'), Producto::imagenPredeterminadaUrl());
    }

    public function test_restaurar_sin_imagen_propia_no_hace_nada(): void
    {
        $this->actingAs($this->admin, 'admin')->delete(self::URL)->assertRedirect(self::URL);

        $this->assertSame(0, Imagen::query()->where('sector', 'producto-defecto')->count());
        $this->assertSame(3, Imagen::query()->count());
        $this->assertFileExists($this->directorio.'/portada.png');
    }

    public function test_la_imagen_de_bmh_se_revalida_con_etag_en_vez_de_descargarse_siempre(): void
    {
        // Embebe el logo (en producción pesa más de 1 MB): tiene que poder
        // cachearse, pero sin quedar vieja cuando se cambia el logo.
        $primera = $this->get('/productos/imagen-predeterminada.svg')->assertOk();
        $etag = $primera->headers->get('ETag');

        $this->assertNotEmpty($etag);
        $this->assertStringContainsString('image/svg+xml', $primera->headers->get('Content-Type'));
        $this->assertStringContainsString('no-cache', $primera->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('no-store', $primera->headers->get('Cache-Control'));

        $this->get('/productos/imagen-predeterminada.svg', ['If-None-Match' => $etag])
            ->assertStatus(304)
            ->assertContent('');

        // Cambia el logo: el ETag viejo ya no sirve.
        File::put($this->directorio.'/logo-nuevo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
        DB::table('imagenes')->insert(['sector' => 'logo-header-blanco', 'path' => 'logo-nuevo.png']);
        $this->app->instance(LogosSitio::class, new LogosSitio($this->directorio));

        $this->get('/productos/imagen-predeterminada.svg', ['If-None-Match' => $etag])->assertOk();
    }

    public function test_si_el_archivo_no_esta_en_disco_se_usa_la_de_bmh_y_no_una_imagen_rota(): void
    {
        DB::table('imagenes')->insert(['sector' => 'producto-defecto', 'path' => 'borrado-a-mano.png']);

        $this->assertNull($this->logos()->urlSiExiste(LogosSitio::PRODUCTO_DEFECTO));
        $this->assertSame(route('producto.placeholder'), Producto::imagenPredeterminadaUrl());
    }
}
