<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use App\Models\ImagenBiblioteca;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Excel de productos: exportar con los códigos de imagen e importar con
 * vista previa, avisos por fila y confirmación. SQLite en memoria.
 */
final class ExcelProductosTest extends TestCase
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
        BibliotecaImagenesTest::esquema();

        $this->dir = sys_get_temp_dir().'/bmh_excel_'.uniqid();
        File::ensureDirectoryExists($this->dir);
        config(['imagenes.directorio' => $this->dir]);
        $this->admin = Admin::query()->create(['name' => 'Tester', 'rol' => 'administrador']);

        DB::table('categorias')->insert(['id' => 1, 'nombre' => 'Solenoides']);
        foreach (['a.png', 'b.png', 'c.png', 'd.png', 'e.png', 'f.png'] as $archivo) {
            file_put_contents("{$this->dir}/{$archivo}", 'x');
        }
        $productos = [
            [1, 'NFSO42', 100, ['a.png', 'b.png']],
            [2, '0094', 50, []],
            [3, 'A-101', 70, ['c.png']],
            [4, 'RP1', 10, []],
            [5, 'RP1', 10, []],
            [6, 'X200', 30, ['d.png']],
            [7, 'Z300', 40, ['f.png']],
            [8, 'Z400', 60, []],
        ];
        foreach ($productos as [$id, $codigo, $precio, $fotos]) {
            DB::table('productos')->insert(['id' => $id, 'codigo' => $codigo, 'nombre' => 'Producto '.$codigo, 'categoria_id' => 1, 'precio' => $precio, 'orden' => 'aa']);
            foreach ($fotos as $i => $foto) {
                DB::table('imagenes')->insert(['path' => $foto, 'sector' => 'producto', 'producto_id' => $id, 'tipo' => $i === 0 ? 'portada' : 'imagen', 'orden' => chr(97 + $i).'a']);
            }
        }
        foreach (['NFSO42' => 'a.png', 'NFSO42-2' => 'b.png', 'A-101' => 'c.png', 'X200' => 'd.png', 'IMG-0001' => 'e.png', 'Z300' => 'f.png'] as $codigo => $path) {
            ImagenBiblioteca::query()->create(['codigo' => $codigo, 'path' => $path, 'nombre_original' => $path]);
        }
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        File::deleteDirectory(storage_path('app/excel-productos'));
        parent::tearDown();
    }

    private function excel(array $filas, array $titulos = ['Código', 'Nombre / Descripción', 'Categoría', 'Precio', 'Imágenes']): UploadedFile
    {
        $libro = new Spreadsheet();
        $hoja = $libro->getActiveSheet();
        $hoja->fromArray($titulos, null, 'A1');
        $hoja->fromArray($filas, null, 'A2', true);
        $ruta = $this->dir.'/'.uniqid().'.xlsx';
        (new Xlsx($libro))->save($ruta);

        return new UploadedFile($ruta, 'productos.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function filasDeEjemplo(): array
    {
        return [
            ['NFSO42', 'x', '', 150, 'NFSO42-2, NFSO42'],   // 2: precio y fotos reordenadas
            [94, '', '', '', 'IMG-0001'],                    // 3: Excel le sacó los ceros a 0094
            ['A-101', '', '', 'abc', ''],                    // 4: precio inválido, fotos sin tocar
            ['X200', '', '', '', '-'],                       // 5: se le sacan las fotos
            ['NOEXISTE', '', '', 10, ''],                    // 6: no existe
            ['RP1', '', '', 5, ''],                          // 7: código repetido en la web
            ['NFSO42', '', '', 200, ''],                     // 8: producto repetido en el Excel
            ['Z300', '', '', '', 'NOPE, Z300'],              // 9: un código que no existe
            ['', 'sin código', '', 5, ''],                   // 10: sin código
            ['Z400', '', '', '', 'c.png'],                   // 11: por nombre de archivo
        ];
    }

    private function subir(UploadedFile $archivo): string
    {
        $respuesta = $this->actingAs($this->admin, 'admin')->post(route('dashboard.productos.excel.previsualizar'), ['archivo' => $archivo]);
        $respuesta->assertRedirect();

        return $respuesta->headers->get('Location');
    }

    public function test_la_vista_previa_muestra_los_cambios_y_un_aviso_por_cada_problema(): void
    {
        $vista = $this->actingAs($this->admin, 'admin')->get($this->subir($this->excel($this->filasDeEjemplo())))->assertOk();
        $reporte = $vista->viewData('reporte');

        $this->assertNull($reporte['fatal']);
        $this->assertSame(['NFSO42', '0094', 'X200', 'Z400'], array_column($reporte['cambios'], 'codigo'));
        $this->assertSame(['productos' => 4, 'precios' => 1, 'imagenes' => 4, 'sin_cambios' => 2, 'errores' => 4, 'advertencias' => 2, 'infos' => 2], $reporte['resumen']);

        $porFila = [];
        foreach ($reporte['avisos'] as $aviso) {
            $porFila[$aviso['fila']] = $aviso['nivel'].': '.$aviso['mensaje'];
        }
        $this->assertStringContainsString('info: Excel le sacó los ceros de adelante al código: «94» se tomó como 0094.', $porFila[3]);
        $this->assertStringContainsString('advertencia: «abc» no es un precio válido', $porFila[4]);
        $this->assertStringContainsString('error: No hay ningún producto con el código «NOEXISTE»', $porFila[6]);
        $this->assertStringContainsString('error: Hay 2 productos con el código «RP1»', $porFila[7]);
        $this->assertStringContainsString('advertencia: El producto NFSO42 ya está en la fila 2', $porFila[8]);
        $this->assertStringContainsString('error: El código de imagen «NOPE» no existe en la biblioteca. Las fotos de este producto no se cambian.', $porFila[9]);
        $this->assertStringContainsString('error: Esta fila no tiene código', $porFila[10]);
        $this->assertStringContainsString('info: «c.png» se reconoció por el nombre del archivo: es la imagen A-101.', $porFila[11]);

        $nfso = $reporte['cambios'][0];
        $this->assertSame(['antes' => 100.0, 'despues' => 150.0], $nfso['precio']);
        $this->assertSame(['NFSO42', 'NFSO42-2'], $nfso['imagenes']['antes']);
        $this->assertSame(['NFSO42-2', 'NFSO42'], $nfso['imagenes']['despues']);
        $this->assertTrue($reporte['cambios'][2]['imagenes']['quitar']);

        // Todavía no cambió nada, y la página lo dice.
        $vista->assertSee('Vista previa: todavía no se cambió nada')->assertSee('Aplicar cambios');
        $this->assertSame(100.0, (float) DB::table('productos')->where('id', 1)->value('precio'));
        $this->assertSame('a.png', DB::table('imagenes')->where('producto_id', 1)->where('tipo', 'portada')->value('path'));
    }

    public function test_confirmar_aplica_los_cambios_una_sola_vez_y_no_borra_archivos(): void
    {
        $preview = $this->subir($this->excel($this->filasDeEjemplo()));
        $confirmar = str_replace('/importar-excel/', '/importar-excel/', $preview).'/confirmar';

        $this->actingAs($this->admin, 'admin')->post($confirmar)->assertRedirect($preview);
        $resultado = $this->actingAs($this->admin, 'admin')->get($preview)->assertOk();
        $resultado->assertSee('Listo: los cambios ya están en la web');

        $fotos = fn (int $id) => DB::table('imagenes')->where('producto_id', $id)->orderByRaw("CASE WHEN tipo = 'portada' THEN 0 ELSE 1 END")->orderBy('orden')->pluck('path', 'tipo')->all();
        $this->assertSame(150.0, (float) DB::table('productos')->where('id', 1)->value('precio'));
        $this->assertSame(['portada' => 'b.png', 'imagen' => 'a.png'], $fotos(1), 'la primera del Excel es la principal');
        $this->assertSame(['portada' => 'e.png'], $fotos(2));
        $this->assertSame([], $fotos(6), 'con «-» se le sacan todas');
        $this->assertSame(['portada' => 'c.png'], $fotos(8));
        $this->assertSame(['portada' => 'c.png'], $fotos(3), 'A-101 sigue con su foto');
        $this->assertSame(['portada' => 'f.png'], $fotos(7), 'con un código que no existe no se tocan');
        $this->assertSame(70.0, (float) DB::table('productos')->where('id', 3)->value('precio'), 'precio inválido: no cambia');
        $this->assertFileExists($this->dir.'/d.png', 'los archivos nunca se borran');

        // Otro clic en «Aplicar» no vuelve a aplicar.
        DB::table('productos')->where('id', 1)->update(['precio' => 999]);
        $this->actingAs($this->admin, 'admin')->post($confirmar)->assertRedirect($preview);
        $this->assertSame(999.0, (float) DB::table('productos')->where('id', 1)->value('precio'));
    }

    public function test_una_fila_sin_tocar_no_cambia_aunque_el_producto_tenga_una_foto_rota(): void
    {
        // W500 tiene una foto cuyo archivo no existe (sin código) y otra en la
        // biblioteca. El Excel exportado sólo muestra la que tiene código.
        file_put_contents("{$this->dir}/h.png", 'x');
        DB::table('productos')->insert(['id' => 9, 'codigo' => 'W500', 'nombre' => 'Con foto rota', 'categoria_id' => 1, 'precio' => 10, 'orden' => 'aa']);
        DB::table('imagenes')->insert([
            ['path' => 'roto.png', 'sector' => 'producto', 'producto_id' => 9, 'tipo' => 'portada', 'orden' => 'aa'],
            ['path' => 'h.png', 'sector' => 'producto', 'producto_id' => 9, 'tipo' => 'imagen', 'orden' => 'ab'],
        ]);
        ImagenBiblioteca::query()->create(['codigo' => 'W500', 'path' => 'h.png']);

        $reporte = $this->actingAs($this->admin, 'admin')->get($this->subir($this->excel([['W500', '', '', 10, 'W500']])))->viewData('reporte');

        $this->assertSame([], $reporte['cambios']);
        $this->assertSame(1, $reporte['resumen']['sin_cambios']);
    }

    public function test_un_excel_sin_columna_codigo_se_rechaza_con_una_explicacion(): void
    {
        $vista = $this->actingAs($this->admin, 'admin')->get($this->subir($this->excel([['x', 10]], ['Producto', 'Precio'])))->assertOk();

        $vista->assertSee('No se puede importar este archivo')->assertSee('No encontré la columna «Código»', false)->assertDontSee('Aplicar cambios');
    }

    public function test_exportar_trae_los_codigos_de_imagen_y_respeta_los_ceros(): void
    {
        $respuesta = $this->actingAs($this->admin, 'admin')->get(route('dashboard.productos.exportar', ['categoria_id' => 'todos']))->assertOk();
        $ruta = $respuesta->baseResponse->getFile()->getPathname();
        $filas = IOFactory::load($ruta)->getSheet(0)->toArray(null, true, false, false);

        $this->assertSame(['Código', 'Nombre / Descripción', 'Categoría', 'Precio', 'Imágenes'], $filas[0]);
        $porCodigo = array_column(array_slice($filas, 1), null, 0);
        $this->assertSame('NFSO42, NFSO42-2', $porCodigo['NFSO42'][4]);
        $this->assertSame('Solenoides', $porCodigo['NFSO42'][2]);
        $this->assertArrayHasKey('0094', $porCodigo, 'el código queda como texto, con sus ceros');
        $this->assertSame('Cómo usar', IOFactory::load($ruta)->getSheet(1)->getTitle());
    }

    public function test_exportar_con_una_busqueda_trae_solo_lo_que_se_ve_filtrado(): void
    {
        $respuesta = $this->actingAs($this->admin, 'admin')
            ->get(route('dashboard.productos.exportar', ['categoria_id' => 1, 'q' => 'z']))
            ->assertOk();
        $filas = IOFactory::load($respuesta->baseResponse->getFile()->getPathname())->getSheet(0)->toArray(null, true, false, false);

        // Lo mismo que el buscador del listado, con los códigos que coinciden primero.
        $this->assertSame(['Z300', 'Z400'], array_column(array_slice($filas, 1), 0));
        $this->assertStringContainsString('_busqueda_z_', (string) $respuesta->headers->get('content-disposition'));

        // Sin texto, toda la categoría.
        $todos = $this->actingAs($this->admin, 'admin')->get(route('dashboard.productos.exportar', ['categoria_id' => 1]));
        $this->assertCount(9, IOFactory::load($todos->baseResponse->getFile()->getPathname())->getSheet(0)->toArray());
    }

    public function test_la_pantalla_de_carga_explica_las_reglas_de_reemplazo(): void
    {
        $this->actingAs($this->admin, 'admin')->get(route('dashboard.productos.excel.importar'))->assertOk()
            ->assertSee('Reemplazan todas las fotos', false)
            ->assertSee('primera es la principal', false)
            ->assertSee('las fotos del producto', false)
            ->assertSee('sacan todas las fotos', false)
            ->assertSee('nunca se borran', false)
            ->assertSee('No se crean productos nuevos', false);
    }
}
