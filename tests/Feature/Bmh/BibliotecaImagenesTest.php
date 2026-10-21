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
            $t->text('descripcion')->nullable();
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

    private function foto(string $nombre): UploadedFile
    {
        $ruta = sys_get_temp_dir().'/'.uniqid().'.png';
        $img = imagecreatetruecolor(300, 300);
        imagefill($img, 0, 0, imagecolorallocate($img, random_int(0, 255), 40, 40));
        imagepng($img, $ruta);

        return new UploadedFile($ruta, $nombre, 'image/png', null, true);
    }

    private function subir(string $nombre, ?string $modo = null): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin, 'admin')->post(
            route('dashboard.imagenes.store'),
            ['imagen' => $this->foto($nombre)] + ($modo ? ['modo' => $modo] : []),
            ['Accept' => 'application/json']
        );
    }

    /** @return list<array{string, string}> path y tipo de las fotos del producto, en orden */
    private function fotos(int $productoId): array
    {
        return DB::table('imagenes')->where('sector', 'producto')->where('producto_id', $productoId)
            ->orderByRaw("CASE WHEN tipo = 'portada' THEN 0 ELSE 1 END")->orderBy('orden')
            ->get(['path', 'tipo'])->map(fn ($f) => [$f->path, $f->tipo])->all();
    }

    public function test_el_codigo_dice_de_que_producto_y_que_foto_es(): void
    {
        $this->producto(1, 'NFSO42', []);
        $this->producto(2, 'LASX 43-48', []);
        $biblioteca = app(BibliotecaImagenes::class);

        $this->assertSame([1, 'NFSO42'], [$biblioteca->destinoDe('NFSO42')['posicion'], $biblioteca->destinoDe('NFSO42')['codigo']]);
        $this->assertSame(3, $biblioteca->destinoDe('NFSO42-3')['posicion']);
        $this->assertSame(1, $biblioteca->destinoDe('LASX-43-48')['posicion'], 'un código de producto con guion no es «foto 48»');
        $this->assertNull($biblioteca->destinoDe('NFSO42-1'));
        $this->assertNull($biblioteca->destinoDe('IMG_2034'));

        $this->assertSame('NFSO42', $biblioteca->sugerencia('FOTO-NFSO42-FRENTE'));
        $this->assertSame('NFSO42', $biblioteca->sugerencia('NFSO42-1'));
        $this->assertSame('NFSO42', $biblioteca->sugerencia('NFS042'), 'una letra de diferencia');
        $this->assertNull($biblioteca->sugerencia('IMG_2034'));
    }

    public function test_subir_una_foto_con_el_codigo_como_nombre_la_asigna_al_producto(): void
    {
        $this->archivo('vieja.png');
        $this->producto(1, 'NFSO42', ['vieja.png']);
        $this->producto(2, 'A-101', []);

        $segunda = $this->subir('NFSO42-2.png')->assertCreated()
            ->assertJsonPath('resultado', 'asignada')
            ->assertJsonPath('imagen.codigo', 'NFSO42-2')
            ->assertJsonPath('mensaje', 'Es la foto 2 de NFSO42.')
            ->assertJsonPath('productos.0.codigo', 'NFSO42');
        $this->subir('a-101.png')->assertCreated()->assertJsonPath('mensaje', 'Es la foto principal de A-101.');

        $nueva = ImagenBiblioteca::query()->where('codigo', 'NFSO42-2')->value('path');
        $this->assertSame([['vieja.png', 'portada'], [$nueva, 'imagen']], $this->fotos(1), 'la que ya tenía se conserva');
        $this->assertSame('portada', $this->fotos(2)[0][1]);
        $this->assertFileExists($this->dir.'/'.$nueva);
        $this->assertStringContainsString('productos/editar?id=1', (string) $segunda->json('productos.0.url'));

        $this->actingAs($this->admin, 'admin')
            ->post(route('dashboard.imagenes.store'), ['imagen' => UploadedFile::fake()->create('lista.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonPath('errors.imagen.0', 'Tiene que ser una imagen JPG, PNG o WEBP.');
    }

    public function test_sin_producto_queda_para_corregir_y_al_corregirlo_se_asigna(): void
    {
        $this->producto(1, 'NFSO42', []);

        $respuesta = $this->subir('foto NFSO42 frente.png')->assertCreated()
            ->assertJsonPath('resultado', 'sin_producto')
            ->assertJsonPath('imagen.codigo', 'FOTO-NFSO42-FRENTE')
            ->assertJsonPath('sugerencia', ['producto' => 'NFSO42', 'codigo' => 'NFSO42'])
            ->assertJsonPath('mensaje', 'No hay ningún producto con el código FOTO-NFSO42-FRENTE. Corregilo y se asigna sola.');
        $this->assertSame([], $this->fotos(1));

        // Si el producto ya tiene principal, se sugiere su próxima foto (o se elige el lugar).
        $this->subir('NFSO42.png')->assertCreated();
        $this->subir('NFSO42 frente.png')->assertJsonPath('sugerencia', ['producto' => 'NFSO42', 'codigo' => 'NFSO42-2']);
        $this->actingAs($this->admin, 'admin')->patchJson($respuesta->json('imagen.update_url'), ['codigo' => 'NFSO42'])
            ->assertStatus(409)->assertJsonPath('fotos.0.codigo', 'NFSO42');

        // La grilla la marca para corregirla.
        $this->actingAs($this->admin, 'admin')->get(route('dashboard.imagenes'))->assertSee('No hay ningún producto con este código');

        $this->actingAs($this->admin, 'admin')->patchJson($respuesta->json('imagen.update_url'), ['codigo' => 'NFSO42-2'])
            ->assertOk()
            ->assertJsonPath('resultado', 'asignada')
            ->assertJsonPath('mensaje', 'Es la foto 2 de NFSO42.');
        $this->assertSame(['portada', 'imagen'], array_column($this->fotos(1), 1));
    }

    public function test_si_ya_hay_una_foto_con_ese_codigo_pregunta_antes_de_tocar_nada(): void
    {
        $this->archivo('actual.png');
        $this->producto(1, 'NFSO42', ['actual.png']);
        app(BibliotecaImagenes::class)->sincronizar();

        $this->subir('NFSO42.png')->assertStatus(409)
            ->assertJsonPath('resultado', 'conflicto')
            ->assertJsonPath('mensaje', 'Ya hay una foto con el código NFSO42 (es la foto principal de NFSO42).')
            ->assertJsonPath('existente.codigo', 'NFSO42')
            // Las fotos del producto, para elegir el lugar.
            ->assertJsonPath('producto.codigo', 'NFSO42')
            ->assertJsonPath('fotos.0.path', 'actual.png')
            ->assertJsonPath('objetivo', 'actual.png');
        $this->assertSame(1, ImagenBiblioteca::query()->count(), 'no se guardó nada');
        $this->assertSame([['actual.png', 'portada']], $this->fotos(1));

        // Reemplazar: misma posición y mismo código; el archivo anterior queda.
        $this->subir('NFSO42.png', 'reemplazar')->assertCreated()
            ->assertJsonPath('resultado', 'reemplazada')
            ->assertJsonPath('mensaje', 'Reemplazó a la foto principal de NFSO42.');
        $nueva = ImagenBiblioteca::query()->where('codigo', 'NFSO42')->value('path');
        $this->assertNotSame('actual.png', $nueva);
        $this->assertSame([[$nueva, 'portada']], $this->fotos(1));
        $this->assertFileExists($this->dir.'/actual.png');

        // Agregar en un lugar elegido: al final, y después como principal.
        $this->subir('NFSO42.png', 'agregar')->assertCreated()
            ->assertJsonPath('imagen.codigo', 'NFSO42-2')
            ->assertJsonPath('mensaje', 'Es la foto 2 de NFSO42.');
        $this->actingAs($this->admin, 'admin')->post(route('dashboard.imagenes.store'), ['imagen' => $this->foto('NFSO42.png'), 'modo' => 'agregar', 'posicion' => 1], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('mensaje', 'Es la foto principal de NFSO42.');
        $this->assertCount(3, $this->fotos(1));
        $this->assertSame($nueva, $this->fotos(1)[1][0], 'la que era principal pasó a ser la segunda');
        // Los códigos siguen el orden: la principal NFSO42, la segunda NFSO42-2…
        $this->assertSame(['NFSO42', 'NFSO42-2', 'NFSO42-3'], array_column(app(BibliotecaImagenes::class)->fotosDe(1), 'codigo'));
    }

    public function test_al_corregir_con_un_codigo_ocupado_se_elige_reemplazar_o_el_lugar(): void
    {
        $this->archivo('principal.png');
        $this->archivo('segunda.png');
        $this->producto(1, 'NFSO42', ['principal.png', 'segunda.png']);
        app(BibliotecaImagenes::class)->sincronizar();
        $suelta = fn () => $this->subir('foto suelta '.uniqid().'.png')->json('imagen.update_url');

        // Pregunta, con las fotos del producto en orden.
        $url = $suelta();
        $this->actingAs($this->admin, 'admin')->patchJson($url, ['codigo' => 'NFSO42'])
            ->assertStatus(409)
            ->assertJsonPath('mensaje', 'El código NFSO42 ya lo tiene otra foto (es la foto principal de NFSO42).')
            ->assertJsonPath('fotos.0.codigo', 'NFSO42')
            ->assertJsonPath('fotos.1.codigo', 'NFSO42-2')
            ->assertJsonPath('objetivo', 'principal.png');
        $this->assertCount(2, $this->fotos(1), 'sin elegir no cambia nada');

        // Ponerla entre las dos.
        $this->actingAs($this->admin, 'admin')->patchJson($url, ['codigo' => 'NFSO42', 'modo' => 'agregar', 'posicion' => 2])
            ->assertOk()->assertJsonPath('mensaje', 'Es la foto 2 de NFSO42.');
        $this->assertSame(['principal.png', 'segunda.png'], [$this->fotos(1)[0][0], $this->fotos(1)[2][0]]);
        $this->assertSame(['NFSO42', 'NFSO42-2', 'NFSO42-3'], array_column(app(BibliotecaImagenes::class)->fotosDe(1), 'codigo'));
        $this->assertSame('NFSO42-3', ImagenBiblioteca::query()->where('path', 'segunda.png')->value('codigo'), 'la que se corrió cambió de código');

        // Reemplazar la principal: la anterior sale del producto y le deja el código.
        $url = $suelta();
        $this->actingAs($this->admin, 'admin')->patchJson($url, ['codigo' => 'NFSO42', 'modo' => 'reemplazar', 'objetivo' => 'principal.png'])
            ->assertOk()->assertJsonPath('mensaje', 'Reemplazó a la foto principal de NFSO42.')->assertJsonPath('imagen.codigo', 'NFSO42');
        $this->assertNotContains('principal.png', array_column($this->fotos(1), 0));
        $this->assertSame('portada', $this->fotos(1)[0][1]);
        $this->assertNull(ImagenBiblioteca::query()->where('path', 'principal.png')->first());
        $this->assertFileExists($this->dir.'/principal.png', 'el archivo no se borra');

        // Una foto que ya no está en el producto no se puede reemplazar.
        $this->actingAs($this->admin, 'admin')->patchJson($suelta(), ['codigo' => 'NFSO42', 'modo' => 'reemplazar', 'objetivo' => 'principal.png'])
            ->assertStatus(422)->assertJsonPath('mensaje', 'Esa foto ya no está en el producto. Recargá la página y probá de nuevo.');
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

    public function test_eliminar_una_imagen_en_uso_pide_confirmar_y_la_saca_del_producto(): void
    {
        $this->archivo('usada.png');
        $this->archivo('segunda.png');
        $this->archivo('libre.png');
        $this->archivo('logo-sitio.png');
        $this->producto(1, 'NFSO42', ['usada.png', 'segunda.png']);
        $usada = ImagenBiblioteca::query()->create(['codigo' => 'NFSO42', 'path' => 'usada.png']);
        $libre = ImagenBiblioteca::query()->create(['codigo' => 'IMG-0001', 'path' => 'libre.png']);

        // Sin confirmar que se saca de los productos, no se toca.
        $this->actingAs($this->admin, 'admin')->deleteJson(route('dashboard.imagenes.destroy', $usada))
            ->assertStatus(422)->assertJsonFragment(['mensaje' => 'Está en 1 producto(s): confirmá que querés sacarla de ahí.']);
        $this->assertFileExists($this->dir.'/usada.png');

        // Confirmado: sale del producto y la siguiente pasa a ser la principal.
        $this->actingAs($this->admin, 'admin')->deleteJson(route('dashboard.imagenes.destroy', $usada), ['quitar_de_productos' => true])->assertOk();
        $this->assertSame([['segunda.png', 'portada']], $this->fotos(1));
        $this->assertFileDoesNotExist($this->dir.'/usada.png');
        $this->assertNull(ImagenBiblioteca::query()->find($usada->id));

        $this->actingAs($this->admin, 'admin')->deleteJson(route('dashboard.imagenes.destroy', $libre))->assertOk();
        $this->assertFileDoesNotExist($this->dir.'/libre.png');

        // Lo que usa otra parte del sitio no se elimina.
        DB::table('imagenes')->insert(['path' => 'logo-sitio.png', 'sector' => 'slider', 'tipo' => 'imagen', 'orden' => 'aa']);
        $slider = ImagenBiblioteca::query()->create(['codigo' => 'IMG-0002', 'path' => 'logo-sitio.png']);
        $this->actingAs($this->admin, 'admin')->deleteJson(route('dashboard.imagenes.destroy', $slider), ['quitar_de_productos' => true])
            ->assertStatus(422)->assertJsonFragment(['mensaje' => 'No se puede eliminar: la usa otra parte del sitio (slider, logos, novedades…).']);
        $this->assertFileExists($this->dir.'/logo-sitio.png');
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
