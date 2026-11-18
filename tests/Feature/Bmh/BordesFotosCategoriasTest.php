<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Categorías › «Emparejar bordes» de la foto (y volver a la original): de una categoría y de todas. La foto
 * emparejada es otro archivo; la original queda. SQLite en memoria; las fotos, en un directorio temporal.
 */
final class BordesFotosCategoriasTest extends TestCase
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
        $this->dir = sys_get_temp_dir().'/bmh_bordes_cat_'.uniqid();
        File::ensureDirectoryExists($this->dir);
        config(['imagenes.directorio' => $this->dir, 'imagenes.ajustes_bordes' => $this->dir.'/ajustes-bordes.json']);

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
        Schema::create('categorias', function (Blueprint $t) {
            $t->id();
            $t->string('nombre')->nullable();
            $t->string('orden')->nullable();
            $t->string('portada')->nullable();
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

    private function categoria(string $foto, bool $escena = false): int
    {
        $img = imagecreatetruecolor(1000, 1000);
        imagefill($img, 0, 0, imagecolorallocate($img, 238, 239, 241));
        $escena
            ? imagefilledrectangle($img, 0, 500, 400, 999, imagecolorallocate($img, 20, 20, 25))
            : imagefilledrectangle($img, 100, 300, 900, 700, imagecolorallocate($img, 40, 45, 55));
        imagepng($img, $this->dir.'/'.$foto);
        imagedestroy($img);

        return DB::table('categorias')->insertGetId(['nombre' => 'CAT '.$foto, 'orden' => 'zz', 'portada' => $foto]);
    }

    public function test_una_categoria_se_empareja_y_vuelve_a_la_original(): void
    {
        $id = $this->categoria('media_c1.png');
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->post(route('categoria.bordes', $id), ['accion' => 'emparejar'])->assertSessionHas('success');
        $this->assertSame('media_c1-parejo.webp', DB::table('categorias')->where('id', $id)->value('portada'));
        $this->assertSame([1080, 1080], array_slice(getimagesize($this->dir.'/media_c1-parejo.webp'), 0, 2));

        $this->actingAs($admin, 'admin')->post(route('categoria.bordes', $id), ['accion' => 'original'])->assertSessionHas('success');
        $this->assertSame('media_c1.png', DB::table('categorias')->where('id', $id)->value('portada'));
    }

    public function test_todas_salteando_las_escenas(): void
    {
        $a = $this->categoria('media_c2.png');
        $b = $this->categoria('media_c3.png', escena: true);

        $this->actingAs($this->admin(), 'admin')->post(route('categoria.bordes.todas'), ['accion' => 'emparejar'])
            ->assertSessionHas('success')->assertSessionHas('warning', fn (string $m) => str_contains($m, 'El fondo no es liso'));

        $this->assertSame('media_c2-parejo.webp', DB::table('categorias')->where('id', $a)->value('portada'));
        $this->assertSame('media_c3.png', DB::table('categorias')->where('id', $b)->value('portada'));
    }

    public function test_un_visitante_no_puede(): void
    {
        $id = $this->categoria('media_c4.png');
        $this->post(route('categoria.bordes', $id), ['accion' => 'emparejar']);
        $this->post(route('categoria.bordes.todas'), ['accion' => 'emparejar']);
        $this->assertSame('media_c4.png', DB::table('categorias')->where('id', $id)->value('portada'));
    }

    public function test_con_otro_margen_se_rehacen(): void
    {
        $id = $this->categoria('media_c5.png');
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->post(route('categoria.bordes', $id), ['accion' => 'emparejar']);

        \App\Services\Imagenes\AjustesBordes::guardar(['categoria' => 22]);
        $this->actingAs($admin, 'admin')->post(route('categoria.bordes.todas'), ['accion' => 'rehacer'])->assertSessionHas('success');

        $this->assertSame('media_c5-parejo-22.webp', DB::table('categorias')->where('id', $id)->value('portada'));
        $this->assertFileDoesNotExist($this->dir.'/media_c5-parejo.webp');
        $this->actingAs($admin, 'admin')->post(route('categoria.bordes', $id), ['accion' => 'original']);
        $this->assertSame('media_c5.png', DB::table('categorias')->where('id', $id)->value('portada'));
    }
}
