<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use App\Models\ImagenBiblioteca;
use App\Services\Imagenes\BibliotecaImagenes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Biblioteca de imágenes: códigos de las imágenes existentes, subida masiva,
 * renombrar y eliminar. Hermético: SQLite en memoria y carpeta temporal.
 */
final class BibliotecaImagenesTest extends TestCase
{
    private string $dir;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite_memoria',
            'database.connections.sqlite_memoria' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);
        DB::purge('sqlite_memoria');
        self::esquema();

        $this->dir = sys_get_temp_dir().'/bmh_biblio_'.uniqid();
        File::ensureDirectoryExists($this->dir);
        config(['imagenes.directorio' => $this->dir]);

        $this->admin = Admin::query()->create(['name' => 'Tester', 'rol' => 'administrador']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    /** Tablas mínimas que usan la biblioteca, el Excel y el layout del admin. */
    public static function esquema(): void
    {
        Schema::create('admins', function (Blueprint $t): void {
            $t->id();
            $t->string('name')->nullable();
            $t->string('username')->nullable();
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->string('rol')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });
        Schema::create('categorias', function (Blueprint $t): void {
            $t->id();
            $t->string('nombre')->nullable();
            $t->string('orden')->nullable();
        });
        Schema::create('productos', function (Blueprint $t): void {
            $t->id();
            $t->string('codigo')->nullable();
            $t->string('nombre')->nullable();
            $t->integer('categoria_id')->nullable();
            $t->decimal('precio', 12, 2)->nullable();
            $t->string('orden')->nullable();
        });
        Schema::create('imagenes', function (Blueprint $t): void {
            $t->id();
            $t->string('path');
            $t->string('sector')->nullable();
            $t->integer('producto_id')->nullable();
            $t->string('tipo')->default('imagen');
            $t->string('orden')->default('aa');
            $t->timestamps();
        });
        Schema::create('pedidos', function (Blueprint $t): void {
            $t->id();
            $t->timestamps();
        });
        (require base_path('database/migrations/2026_10_19_000002_create_biblioteca_imagenes_table.php'))->up();
        // El layout del admin muestra el logo.
        DB::table('imagenes')->insert(['path' => 'logo.png', 'sector' => 'logo2', 'tipo' => 'imagen', 'orden' => 'aa']);
    }

    private function archivo(string $nombre): void
    {
        $img = imagecreatetruecolor(40, 40);
        imagefill($img, 0, 0, imagecolorallocate($img, 30, 120, 200));
        imagepng($img, "{$this->dir}/{$nombre}");
    }

    private function producto(int $id, string $codigo, array $fotos): void
    {
        DB::table('productos')->insert(['id' => $id, 'codigo' => $codigo, 'nombre' => 'Producto '.$codigo, 'precio' => 100]);
        foreach ($fotos as $i => $foto) {
            DB::table('imagenes')->insert(['path' => $foto, 'sector' => 'producto', 'producto_id' => $id, 'tipo' => $i === 0 ? 'portada' : 'imagen', 'orden' => chr(97 + $i).'a']);
        }
    }

    private function codigos(): array
    {
        return ImagenBiblioteca::query()->orderBy('codigo')->pluck('codigo', 'path')->all();
    }

    public function test_las_imagenes_existentes_toman_el_codigo_del_producto(): void
    {
        foreach (['a.png', 'b.png', 'c.png', 'd.png', 'compartida.png'] as $f) {
            $this->archivo($f);
        }
        // La principal es la portada aunque esté cargada después.
        DB::table('productos')->insert(['id' => 1, 'codigo' => 'NFSO42', 'nombre' => 'Solenoide']);
        DB::table('imagenes')->insert([
            ['path' => 'b.png', 'sector' => 'producto', 'producto_id' => 1, 'tipo' => 'imagen', 'orden' => 'ab'],
            ['path' => 'a.png', 'sector' => 'producto', 'producto_id' => 1, 'tipo' => 'portada', 'orden' => 'aa'],
            ['path' => 'c.png', 'sector' => 'producto', 'producto_id' => 1, 'tipo' => 'imagen', 'orden' => 'ac'],
        ]);
        $this->producto(2, 'PLH7/1', ['d.png', 'compartida.png']);
        // Un archivo que usan dos productos se registra una vez.
        DB::table('imagenes')->insert(['path' => 'compartida.png', 'sector' => 'producto', 'producto_id' => 1, 'tipo' => 'imagen', 'orden' => 'ad']);

        $resultado = app(BibliotecaImagenes::class)->sincronizar();

        $this->assertSame(5, $resultado['registradas']);
        $codigos = $this->codigos();
        $this->assertSame('NFSO42', $codigos['a.png']);
        $this->assertSame('NFSO42-2', $codigos['b.png']);
        $this->assertSame('NFSO42-3', $codigos['c.png']);
        $this->assertSame('NFSO42-4', $codigos['compartida.png']);
        $this->assertSame('PLH7-1', $codigos['d.png'], 'la barra no vale en un código');

        // Correrlo de nuevo no hace nada.
        $this->assertSame(0, app(BibliotecaImagenes::class)->sincronizar()['registradas']);
    }

    public function test_si_falta_el_archivo_de_la_principal_la_segunda_igual_es_codigo_2(): void
    {
        $this->archivo('segunda.png');
        $this->producto(1, 'X100', ['no-existe.png', 'segunda.png']);

        $resultado = app(BibliotecaImagenes::class)->sincronizar();

        $this->assertSame(1, $resultado['sin_archivo']);
        $this->assertSame(['segunda.png' => 'X100-2'], $this->codigos());
    }

    public function test_el_codigo_de_una_imagen_nueva_sale_del_nombre_del_archivo(): void
    {
        $this->archivo('ya.png');
        $this->producto(1, 'NFSO42', ['ya.png']);
        $this->producto(2, 'A-101', []);
        $biblioteca = app(BibliotecaImagenes::class);
        $biblioteca->sincronizar();

        $this->assertSame('NFSO42-2', $biblioteca->sugerirCodigo('NFSO42.jpg')[0], 'NFSO42 ya lo tiene otra');
        $this->assertSame('A-101', $biblioteca->sugerirCodigo('a-101.PNG')[0]);
        $this->assertSame('NFSO42-5', $biblioteca->sugerirCodigo('NFSO42 (5).jpg')[0]);
        $this->assertSame('A-101', $biblioteca->sugerirCodigo('foto A-101 frente.jpg')[0]);
        [$generico, $motivo] = $biblioteca->sugerirCodigo('IMG_20261019.jpg');
        $this->assertSame('IMG-0001', $generico);
        $this->assertStringContainsString('genérico', $motivo);
    }

    public function test_subir_varias_imagenes_les_da_codigo(): void
    {
        $this->producto(1, 'NFSO42', []);
        $foto = function (string $nombre): UploadedFile {
            $ruta = sys_get_temp_dir().'/'.uniqid().'.png';
            $img = imagecreatetruecolor(300, 300);
            imagefill($img, 0, 0, imagecolorallocate($img, 200, 40, 40));
            imagepng($img, $ruta);

            return new UploadedFile($ruta, $nombre, 'image/png', null, true);
        };

        $primera = $this->actingAs($this->admin, 'admin')->post(route('dashboard.imagenes.store'), ['imagen' => $foto('NFSO42.png')], ['Accept' => 'application/json']);
        $segunda = $this->actingAs($this->admin, 'admin')->post(route('dashboard.imagenes.store'), ['imagen' => $foto('otra cosa.png')], ['Accept' => 'application/json']);

        $primera->assertCreated()->assertJsonPath('imagen.codigo', 'NFSO42');
        $segunda->assertCreated()->assertJsonPath('imagen.codigo', 'IMG-0001');
        $this->assertStringContainsString('código del producto NFSO42', $primera->json('motivo'));
        $this->assertFileExists($this->dir.'/'.ImagenBiblioteca::query()->where('codigo', 'NFSO42')->value('path'));

        $this->actingAs($this->admin, 'admin')
            ->post(route('dashboard.imagenes.store'), ['imagen' => UploadedFile::fake()->create('lista.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonPath('errors.imagen.0', 'Tiene que ser una imagen JPG, PNG o WEBP.');
    }

    public function test_renombrar_valida_el_formato_y_que_no_se_repita(): void
    {
        $a = ImagenBiblioteca::query()->create(['codigo' => 'IMG-0001', 'path' => 'a.webp']);
        ImagenBiblioteca::query()->create(['codigo' => 'NFSO42', 'path' => 'b.webp']);
        $url = route('dashboard.imagenes.update', $a);

        $this->actingAs($this->admin, 'admin')->patchJson($url, ['codigo' => 'nfso 42'])
            ->assertStatus(422)->assertJsonFragment(['mensaje' => 'Sólo letras, números, guion (-), guion bajo (_) y punto, sin espacios. Por ejemplo: NFSO-42']);
        $this->actingAs($this->admin, 'admin')->patchJson($url, ['codigo' => 'NFSO42'])
            ->assertStatus(422)->assertJsonFragment(['mensaje' => 'El código NFSO42 ya lo tiene otra imagen.']);
        $this->actingAs($this->admin, 'admin')->patchJson($url, ['codigo' => 'NFSO42-FRENTE'])
            ->assertOk()->assertJsonPath('imagen.codigo', 'NFSO42-FRENTE');
    }

    public function test_solo_se_elimina_una_imagen_que_no_se_usa(): void
    {
        $this->archivo('usada.png');
        $this->archivo('libre.png');
        $this->producto(1, 'NFSO42', ['usada.png']);
        $usada = ImagenBiblioteca::query()->create(['codigo' => 'NFSO42', 'path' => 'usada.png']);
        $libre = ImagenBiblioteca::query()->create(['codigo' => 'IMG-0001', 'path' => 'libre.png']);

        $this->actingAs($this->admin, 'admin')->deleteJson(route('dashboard.imagenes.destroy', $usada))
            ->assertStatus(422)->assertJsonFragment(['mensaje' => 'No se puede eliminar: la usa 1 producto(s).']);
        $this->assertFileExists($this->dir.'/usada.png');

        $this->actingAs($this->admin, 'admin')->deleteJson(route('dashboard.imagenes.destroy', $libre))->assertOk();
        $this->assertFileDoesNotExist($this->dir.'/libre.png');
        $this->assertNull(ImagenBiblioteca::query()->find($libre->id));
    }

    public function test_la_pagina_muestra_las_imagenes_con_su_codigo_y_donde_se_usan(): void
    {
        $this->archivo('a.png');
        $this->producto(1, 'NFSO42', ['a.png']);

        $pagina = $this->actingAs($this->admin, 'admin')->get(route('dashboard.imagenes'))->assertOk();

        $pagina->assertSee('Biblioteca de imágenes')
            ->assertSee('Arrastrá las imágenes acá', false)
            ->assertSee('NFSO42')
            ->assertSee('Principal de');
        $this->actingAs($this->admin, 'admin')->get(route('dashboard.imagenes', ['filtro' => 'sin-asignar']))->assertOk()->assertSee('No hay imágenes con estos filtros');
    }

    public function test_un_visitante_no_entra(): void
    {
        $this->get(route('dashboard.imagenes'))->assertRedirect('/login/admin');
    }
}
