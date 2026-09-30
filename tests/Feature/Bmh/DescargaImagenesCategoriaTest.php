<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use ZipArchive;

/**
 * Admin → Productos filtrado por categoría → «Descargar imágenes».
 *
 * Hermético: SQLite en memoria y las imágenes en una carpeta temporal. Se
 * abre el ZIP que devuelve la ruta y se revisan los nombres de adentro.
 */
final class DescargaImagenesCategoriaTest extends TestCase
{
    private const URL = '/dashboard/productos/imagenes-zip';

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
        });
        Schema::create('productos', function (Blueprint $t) {
            $t->id();
            $t->string('codigo')->nullable();
            $t->string('nombre')->nullable();
            $t->integer('categoria_id')->nullable();
        });
        Schema::create('imagenes', function (Blueprint $t) {
            $t->id();
            $t->string('path');
            $t->string('sector')->nullable();
            $t->string('tipo')->default('imagen');
            $t->string('orden')->default('aa');
            $t->integer('producto_id')->nullable();
        });

        $this->dir = sys_get_temp_dir().'/bmh_zip_'.uniqid();
        File::ensureDirectoryExists($this->dir);
        config(['imagenes.directorio' => $this->dir]);

        $this->admin = Admin::query()->create(['name' => 'Tester', 'rol' => 'administrador']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    private function imagen(int $productoId, string $archivo, string $tipo = 'imagen', string $orden = 'aa'): void
    {
        file_put_contents("{$this->dir}/{$archivo}", "contenido de {$archivo}");
        DB::table('imagenes')->insert(['path' => $archivo, 'sector' => 'producto', 'tipo' => $tipo, 'orden' => $orden, 'producto_id' => $productoId]);
    }

    /** @return array<string, string> nombre dentro del ZIP => contenido */
    private function descargar(int $categoriaId): array
    {
        $respuesta = $this->actingAs($this->admin, 'admin')->get(self::URL.'?categoria_id='.$categoriaId);
        $respuesta->assertOk();
        $this->assertStringContainsString('attachment', (string) $respuesta->headers->get('Content-Disposition'));

        $ruta = $this->dir.'/descarga.zip';
        file_put_contents($ruta, $respuesta->streamedContent());
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($ruta) === true, 'el ZIP tiene que abrirse');

        $contenido = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nombre = $zip->getNameIndex($i);
            $contenido[$nombre] = $zip->getFromIndex($i);
        }
        $zip->close();

        return $contenido;
    }

    public function test_nombra_cada_imagen_con_el_codigo_y_numera_si_hay_varias(): void
    {
        DB::table('categorias')->insert([['id' => 1, 'nombre' => 'Arranques'], ['id' => 2, 'nombre' => 'Alternadores']]);
        DB::table('productos')->insert([
            ['id' => 10, 'codigo' => 'ARR-100', 'nombre' => 'Con tres', 'categoria_id' => 1],
            ['id' => 11, 'codigo' => 'ARR-200', 'nombre' => 'Con una', 'categoria_id' => 1],
            ['id' => 12, 'codigo' => 'ARR-300', 'nombre' => 'Sin fotos', 'categoria_id' => 1],
            ['id' => 20, 'codigo' => 'ALT-900', 'nombre' => 'Otra categoría', 'categoria_id' => 2],
        ]);
        // La portada va primera aunque se haya cargado última.
        $this->imagen(10, 'b.webp', orden: 'bb');
        $this->imagen(10, 'c.jpg', orden: 'cc');
        $this->imagen(10, 'portada.png', tipo: 'portada');
        $this->imagen(11, 'unica.webp', tipo: 'portada');
        $this->imagen(20, 'otra.webp', tipo: 'portada');

        $zip = $this->descargar(1);

        $this->assertSame([
            'ARR-100-1.png', 'ARR-100-2.webp', 'ARR-100-3.jpg', 'ARR-200.webp', 'sin-imagenes.txt',
        ], array_keys($zip));
        $this->assertSame('contenido de portada.png', $zip['ARR-100-1.png']);
        $this->assertStringContainsString('ARR-300 — Sin fotos', $zip['sin-imagenes.txt']);
        $this->assertArrayNotHasKey('ALT-900.webp', $zip);
    }

    public function test_codigos_repetidos_o_con_caracteres_invalidos_no_se_pisan(): void
    {
        DB::table('categorias')->insert(['id' => 1, 'nombre' => 'Varios']);
        DB::table('productos')->insert([
            ['id' => 30, 'codigo' => 'AB/12', 'nombre' => 'Uno', 'categoria_id' => 1],
            ['id' => 31, 'codigo' => 'AB/12', 'nombre' => 'Dos', 'categoria_id' => 1],
            ['id' => 32, 'codigo' => '', 'nombre' => 'Sin código', 'categoria_id' => 1],
        ]);
        $this->imagen(30, 'uno.webp', tipo: 'portada');
        $this->imagen(31, 'dos.webp', tipo: 'portada');
        $this->imagen(32, 'tres.webp', tipo: 'portada');

        $nombres = array_keys($this->descargar(1));

        $this->assertEqualsCanonicalizing(['AB-12.webp', 'AB-12_31.webp', 'producto-32.webp'], $nombres);
    }

    public function test_omite_imagenes_cuyo_archivo_no_existe(): void
    {
        DB::table('categorias')->insert(['id' => 1, 'nombre' => 'Rota']);
        DB::table('productos')->insert(['id' => 40, 'codigo' => 'ROT-1', 'nombre' => 'Rota', 'categoria_id' => 1]);
        $this->imagen(40, 'existe.webp', tipo: 'portada');
        DB::table('imagenes')->insert(['path' => 'borrada.webp', 'sector' => 'producto', 'tipo' => 'imagen', 'producto_id' => 40]);

        $this->assertSame(['ROT-1.webp'], array_keys($this->descargar(1)));
    }

    public function test_una_categoria_sin_imagenes_responde_404(): void
    {
        DB::table('categorias')->insert(['id' => 1, 'nombre' => 'Vacía']);
        DB::table('productos')->insert(['id' => 50, 'codigo' => 'V-1', 'nombre' => 'Sin fotos', 'categoria_id' => 1]);

        $this->actingAs($this->admin, 'admin')->get(self::URL.'?categoria_id=1')->assertNotFound();
        $this->actingAs($this->admin, 'admin')->get(self::URL.'?categoria_id=999')->assertNotFound();
    }

    public function test_un_visitante_no_puede_descargar(): void
    {
        DB::table('categorias')->insert(['id' => 1, 'nombre' => 'Arranques']);

        $this->get(self::URL.'?categoria_id=1')->assertRedirect('/login/admin');
    }
}
