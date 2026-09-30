<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * php artisan imagenes:optimizar — pasa a WebP las imágenes en uso.
 *
 * Hermético: SQLite en memoria y las imágenes en una carpeta temporal.
 */
final class OptimizarImagenesCommandTest extends TestCase
{
    private string $dir;

    /** @var list<string> registros que había antes de cada test */
    private array $registrosPrevios = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('GD sin soporte WebP');
        }

        config([
            'database.default' => 'sqlite_memoria',
            'database.connections.sqlite_memoria' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);
        DB::purge('sqlite_memoria');

        Schema::create('imagenes', function (Blueprint $t) {
            $t->id();
            $t->string('path');
            $t->string('path_mobile')->nullable();
            $t->string('sector')->nullable();
            $t->integer('producto_id')->nullable();
        });
        Schema::create('categorias', function (Blueprint $t) {
            $t->id();
            $t->string('nombre')->nullable();
            $t->string('portada')->nullable();
        });
        Schema::create('novedades', function (Blueprint $t) {
            $t->id();
            $t->string('portada')->nullable();
            $t->text('texto')->nullable();
        });

        $this->dir = sys_get_temp_dir().'/bmh_lote_'.uniqid();
        File::ensureDirectoryExists($this->dir);
        config([
            'imagenes.directorio' => $this->dir,
            'imagenes.excluidas' => ['fija.png'],
            'imagenes.sectores_excluidos' => ['logo'],
        ]);

        $this->registrosPrevios = glob(storage_path('app/optimizacion-imagenes/*.jsonl')) ?: [];
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        foreach (array_diff(glob(storage_path('app/optimizacion-imagenes/*.jsonl')) ?: [], $this->registrosPrevios) as $nuevo) {
            @unlink($nuevo);
        }
        parent::tearDown();
    }

    private function foto(string $nombre, int $ancho = 900, int $alto = 700): void
    {
        $img = imagecreatetruecolor($ancho, $alto);
        mt_srand(crc32($nombre));
        for ($y = 0; $y < $alto; $y += 3) {
            for ($x = 0; $x < $ancho; $x += 3) {
                $r = mt_rand(-4, 4);
                imagefilledrectangle($img, $x, $y, $x + 2, $y + 2, imagecolorallocate($img,
                    (int) (60 + 150 * $x / $ancho) + $r, (int) (90 + 90 * $y / $alto) + $r, 120 + $r));
            }
        }
        str_ends_with($nombre, '.png') ? imagepng($img, "{$this->dir}/{$nombre}", 9) : imagejpeg($img, "{$this->dir}/{$nombre}", 95);
    }

    private function sembrar(): void
    {
        foreach (['producto.png', 'compartida.png', 'huerfana.png', 'fija.png', 'logo.png', 'embebida.png', 'foto.jpg'] as $archivo) {
            $this->foto($archivo);
        }

        DB::table('imagenes')->insert([
            ['path' => 'producto.png', 'sector' => 'producto', 'producto_id' => 1],
            ['path' => 'compartida.png', 'sector' => 'producto', 'producto_id' => 2],
            ['path' => 'fija.png', 'sector' => 'producto', 'producto_id' => 3],
            ['path' => 'logo.png', 'sector' => 'logo', 'producto_id' => null],
            ['path' => 'embebida.png', 'sector' => 'producto', 'producto_id' => 4],
            ['path' => 'foto.jpg', 'sector' => 'home-slider', 'producto_id' => null],
        ]);
        // La misma imagen usada en otra tabla: se actualizan las dos.
        DB::table('categorias')->insert(['nombre' => 'Arranques', 'portada' => 'compartida.png']);
        // Metida dentro de un texto: no se puede renombrar con seguridad.
        DB::table('novedades')->insert(['portada' => null, 'texto' => '<p>Mirá <img src="imagenes/embebida.png"></p>']);
    }

    public function test_convierte_las_imagenes_en_uso_actualiza_todas_sus_referencias_y_borra_el_original(): void
    {
        $this->sembrar();

        $this->artisan('imagenes:optimizar', ['--pausa' => 0])->assertSuccessful();

        $this->assertSame('producto.webp', DB::table('imagenes')->where('producto_id', 1)->value('path'));
        $this->assertSame('compartida.webp', DB::table('imagenes')->where('producto_id', 2)->value('path'));
        $this->assertSame('compartida.webp', DB::table('categorias')->value('portada'));
        $this->assertSame('foto.webp', DB::table('imagenes')->where('sector', 'home-slider')->value('path'));

        foreach (['producto', 'compartida', 'foto'] as $base) {
            $this->assertFileExists("{$this->dir}/{$base}.webp");
            $this->assertSame(IMAGETYPE_WEBP, getimagesize("{$this->dir}/{$base}.webp")[2]);
        }
        $this->assertFileDoesNotExist("{$this->dir}/producto.png");
        $this->assertFileDoesNotExist("{$this->dir}/compartida.png");
        $this->assertFileDoesNotExist("{$this->dir}/foto.jpg");
    }

    public function test_no_toca_huerfanas_excluidas_logos_ni_referencias_dentro_de_un_texto(): void
    {
        $this->sembrar();

        $this->artisan('imagenes:optimizar', ['--pausa' => 0])->assertSuccessful();

        // Sin referencias en la base: puede estar enlazada desde afuera.
        $this->assertFileExists("{$this->dir}/huerfana.png");
        // Nombre fijo en el código.
        $this->assertFileExists("{$this->dir}/fija.png");
        $this->assertSame('fija.png', DB::table('imagenes')->where('producto_id', 3)->value('path'));
        // Logos y favicon quedan en su formato.
        $this->assertFileExists("{$this->dir}/logo.png");
        $this->assertSame('logo.png', DB::table('imagenes')->where('sector', 'logo')->value('path'));
        // Aparece dentro de HTML: renombrarla rompería ese texto.
        $this->assertFileExists("{$this->dir}/embebida.png");
        $this->assertSame('embebida.png', DB::table('imagenes')->where('producto_id', 4)->value('path'));
    }

    public function test_deja_un_registro_de_cada_cambio_para_poder_restaurar(): void
    {
        $this->sembrar();

        $this->artisan('imagenes:optimizar', ['--pausa' => 0])->assertSuccessful();

        $registro = array_values(array_diff(glob(storage_path('app/optimizacion-imagenes/*.jsonl')) ?: [], $this->registrosPrevios));
        $this->assertCount(1, $registro);
        $lineas = array_map(fn ($l) => json_decode($l, true), file($registro[0], FILE_IGNORE_NEW_LINES));
        $porNombre = array_column($lineas, null, 'viejo');

        $this->assertEqualsCanonicalizing(['producto.png', 'compartida.png', 'foto.jpg'], array_keys($porNombre));
        $this->assertSame('compartida.webp', $porNombre['compartida.png']['nuevo']);
        $this->assertSame(['imagenes.path' => 1, 'categorias.portada' => 1], $porNombre['compartida.png']['referencias']);
        $this->assertLessThan($porNombre['producto.png']['bytes_antes'], $porNombre['producto.png']['bytes_despues']);
    }

    public function test_en_modo_prueba_no_cambia_nada(): void
    {
        $this->sembrar();
        $antes = scandir($this->dir);

        $this->artisan('imagenes:optimizar', ['--dry-run' => true, '--pausa' => 0])
            ->expectsOutputToContain('[prueba]')
            ->assertSuccessful();

        $this->assertSame($antes, scandir($this->dir));
        $this->assertSame('producto.png', DB::table('imagenes')->where('producto_id', 1)->value('path'));
    }

    public function test_correrlo_de_nuevo_no_hace_nada(): void
    {
        $this->sembrar();
        $this->artisan('imagenes:optimizar', ['--pausa' => 0])->assertSuccessful();
        $despuesDeLaPrimera = scandir($this->dir);

        $this->artisan('imagenes:optimizar', ['--pausa' => 0])
            ->expectsOutputToContain('Optimizadas 0 de 0')
            ->assertSuccessful();

        $this->assertSame($despuesDeLaPrimera, scandir($this->dir));
    }

    public function test_respeta_el_limite(): void
    {
        $this->sembrar();

        $this->artisan('imagenes:optimizar', ['--limite' => 1, '--pausa' => 0])->assertSuccessful();

        $convertidas = DB::table('imagenes')->where('path', 'like', '%.webp')->count();
        $this->assertSame(1, $convertidas);
    }

    public function test_si_el_nombre_webp_ya_existe_no_lo_pisa(): void
    {
        $this->sembrar();
        file_put_contents("{$this->dir}/producto.webp", 'otro archivo que ya existía');

        $this->artisan('imagenes:optimizar', ['--pausa' => 0])->assertSuccessful();

        $nuevo = DB::table('imagenes')->where('producto_id', 1)->value('path');
        $this->assertMatchesRegularExpression('/^producto-[0-9a-f]+\.webp$/', $nuevo);
        $this->assertSame('otro archivo que ya existía', file_get_contents("{$this->dir}/producto.webp"));
        $this->assertFileExists("{$this->dir}/{$nuevo}");
    }
}
