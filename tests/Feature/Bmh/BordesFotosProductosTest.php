<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * «Emparejar bordes» de las fotos de productos: de una foto, de un producto, de todas (de a tandas) y al subir. La
 * foto cambia donde se usa (la ficha y la biblioteca) y se puede volver a la original. SQLite en memoria; las fotos,
 * en un directorio temporal.
 */
final class BordesFotosProductosTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite_memoria',
            'database.connections.sqlite_memoria' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);
        DB::purge('sqlite_memoria');
        $this->dir = sys_get_temp_dir().'/bmh_bordes_'.uniqid();
        File::ensureDirectoryExists($this->dir);
        config(['imagenes.directorio' => $this->dir]);

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
        Schema::create('productos', function (Blueprint $t) {
            $t->id();
            $t->string('codigo')->nullable();
            $t->string('nombre')->nullable();
            $t->integer('estado')->nullable();
            $t->timestamps();
        });
        Schema::create('imagenes', function (Blueprint $t) {
            $t->id();
            $t->string('path')->nullable();
            $t->string('path_mobile')->nullable();
            $t->unsignedBigInteger('producto_id')->nullable();
            $t->string('sector')->nullable();
            $t->string('tipo')->nullable();
            $t->string('orden')->nullable();
            $t->timestamps();
        });
        Schema::create('biblioteca_imagenes', function (Blueprint $t) {
            $t->id();
            $t->string('codigo', 60)->unique();
            $t->string('path')->unique();
            $t->string('nombre_original')->nullable();
            $t->unsignedBigInteger('bytes')->nullable();
            $t->unsignedInteger('ancho')->nullable();
            $t->unsignedInteger('alto')->nullable();
            $t->timestamps();
        });
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    private function admin(): Admin
    {
        return Admin::query()->forceCreate(['name' => 'Admin', 'username' => 'admin', 'rol' => 'administrador', 'password' => 'x']);
    }

    /** Foto con fondo blanco y el producto (rectángulo oscuro) chico en una esquina. */
    private function foto(string $nombre, bool $escena = false): string
    {
        $img = imagecreatetruecolor(1000, 1000);
        imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
        if ($escena) {
            imagefilledrectangle($img, 0, 500, 400, 999, imagecolorallocate($img, 20, 20, 25));
        } else {
            imagefilledrectangle($img, 80, 120, 380, 320, imagecolorallocate($img, 40, 60, 90));
        }
        imagepng($img, $this->dir.'/'.$nombre);
        imagedestroy($img);

        return $nombre;
    }

    private function producto(string $codigo, string ...$fotos): int
    {
        $id = DB::table('productos')->insertGetId(['codigo' => $codigo, 'nombre' => 'Producto '.$codigo]);
        foreach ($fotos as $i => $foto) {
            DB::table('imagenes')->insert(['path' => $foto, 'producto_id' => $id, 'sector' => 'producto', 'tipo' => $i === 0 ? 'portada' : null]);
            DB::table('biblioteca_imagenes')->insert(['codigo' => $codigo.($i ? '-'.($i + 1) : ''), 'path' => $foto, 'nombre_original' => $foto]);
        }

        return $id;
    }

    public function test_se_emparejan_las_fotos_de_un_producto_y_se_vuelve_a_las_originales(): void
    {
        $id = $this->producto('ZZ1', $this->foto('media_a.png'), $this->foto('media_b.png'));
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->post(route('producto.bordes', $id), ['accion' => 'emparejar'])->assertSessionHas('success');

        $this->assertSame(['media_a-parejo.webp', 'media_b-parejo.webp'], DB::table('imagenes')->orderBy('id')->pluck('path')->all());
        $this->assertSame(['media_a-parejo.webp', 'media_b-parejo.webp'], DB::table('biblioteca_imagenes')->orderBy('id')->pluck('path')->all());
        // El nombre con el que se subió no cambia, y la biblioteca tiene las medidas de la emparejada.
        $this->assertSame('media_a.png', DB::table('biblioteca_imagenes')->where('codigo', 'ZZ1')->value('nombre_original'));
        $this->assertSame(800, (int) DB::table('biblioteca_imagenes')->where('codigo', 'ZZ1')->value('ancho'));
        $this->assertFileExists($this->dir.'/media_a.png');

        $this->actingAs($admin, 'admin')->post(route('producto.bordes', $id), ['accion' => 'original'])->assertSessionHas('success');
        $this->assertSame(['media_a.png', 'media_b.png'], DB::table('imagenes')->orderBy('id')->pluck('path')->all());
        $this->assertSame(['media_a.png', 'media_b.png'], DB::table('biblioteca_imagenes')->orderBy('id')->pluck('path')->all());
    }

    public function test_se_empareja_una_sola_foto(): void
    {
        $id = $this->producto('ZZ2', $this->foto('media_c.png'), $this->foto('media_d.png'));
        $segunda = DB::table('imagenes')->where('path', 'media_d.png')->value('id');

        $this->actingAs($this->admin(), 'admin')->post(route('producto.bordes', $id), ['accion' => 'emparejar', 'imagen' => $segunda]);

        $this->assertSame(['media_c.png', 'media_d-parejo.webp'], DB::table('imagenes')->orderBy('id')->pluck('path')->all());
    }

    public function test_todas_de_a_tandas_salteando_las_que_no_se_pueden(): void
    {
        $this->producto('ZZ3', $this->foto('media_e.png'));
        $this->producto('ZZ4', $this->foto('media_f.png', escena: true));
        $this->producto('ZZ5', $this->foto('media_g.png'));
        $admin = $this->admin();

        $r = $this->actingAs($admin, 'admin')->postJson(route('dashboard.imagenes.bordes'), ['accion' => 'emparejar'])->assertOk()->json();
        $this->assertSame(2, $r['hechas']);
        $this->assertTrue($r['terminado']);
        $this->assertCount(1, $r['salteadas']);
        $this->assertSame(['ZZ4'], $r['salteadas'][0]['productos']);
        $this->assertStringContainsString('fondo no es liso', $r['salteadas'][0]['motivo']);
        $this->assertSame(['media_e-parejo.webp', 'media_f.png', 'media_g-parejo.webp'], DB::table('imagenes')->orderBy('id')->pluck('path')->all());

        $r = $this->actingAs($admin, 'admin')->postJson(route('dashboard.imagenes.bordes'), ['accion' => 'original'])->assertOk()->json();
        $this->assertSame(2, $r['hechas']);
        $this->assertSame(['media_e.png', 'media_f.png', 'media_g.png'], DB::table('imagenes')->orderBy('id')->pluck('path')->all());
    }

    public function test_al_reemplazar_una_foto_con_la_casilla_marcada_queda_emparejada(): void
    {
        $this->producto('ZZ6', $this->foto('media_h.png'));
        $imagen = DB::table('imagenes')->value('id');
        $subida = UploadedFile::fake()->createWithContent('nueva.png', (string) file_get_contents($this->dir.'/'.$this->foto('tmp.png')));

        $this->actingAs($this->admin(), 'admin')
            ->put(route('producto.imagen.update', ['id' => $imagen]), ['id' => $imagen, 'imagen' => $subida, 'orden' => 'aa', 'emparejar_bordes' => '1']);

        $path = DB::table('imagenes')->where('id', $imagen)->value('path');
        $this->assertStringEndsWith('-parejo.webp', $path);
        $this->assertFileDoesNotExist($this->dir.'/media_h.png', 'la foto reemplazada se borra');
    }

    public function test_un_visitante_no_puede_emparejar(): void
    {
        $id = $this->producto('ZZ7', $this->foto('media_i.png'));

        $this->post(route('producto.bordes', $id), ['accion' => 'emparejar']);
        $this->postJson(route('dashboard.imagenes.bordes'), ['accion' => 'emparejar']);

        $this->assertSame('media_i.png', DB::table('imagenes')->value('path'));
    }
}
