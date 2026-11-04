<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Admin → Productos → «Actualizar desde Excel: Precios»: actualiza y avisa
 * cuántos productos se actualizaron y cuáles códigos no (con Excel para bajar).
 */
final class ActualizacionPreciosTest extends TestCase
{
    private string $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = sys_get_temp_dir().'/bmh-precios-'.uniqid();
        File::ensureDirectoryExists($this->storage.'/app');
        File::ensureDirectoryExists($this->storage.'/framework/views');
        $this->app->useStoragePath($this->storage);
        config(['view.compiled' => $this->storage.'/framework/views']);

        config([
            'database.default' => 'sqlite_memoria',
            'database.connections.sqlite_memoria' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);
        DB::purge('sqlite_memoria');

        Schema::create('categorias', function (Blueprint $t): void {
            $t->id();
            $t->string('nombre');
        });
        Schema::create('productos', function (Blueprint $t): void {
            $t->id();
            $t->string('codigo');
            $t->string('nombre');
            $t->text('descripcion')->nullable();
            $t->integer('categoria_id');
            $t->string('orden');
            $t->boolean('destacada')->default(false);
            $t->decimal('precio', 12, 2)->nullable();
        });
        Schema::create('imagenes', function (Blueprint $t): void {
            $t->id();
            $t->string('sector');
            $t->string('tipo')->nullable();
            $t->string('path')->nullable();
            $t->integer('producto_id')->nullable();
        });
        Schema::create('pedidos', function (Blueprint $t): void {
            $t->id();
            $t->timestamps();
        });
        DB::table('imagenes')->insert(['sector' => 'logo2', 'path' => 'logo2.png']);
        DB::table('categorias')->insert(['id' => 1, 'nombre' => 'Repuestos']);

        $producto = fn (int $id, string $codigo, float $precio) => ['id' => $id, 'codigo' => $codigo, 'nombre' => "Producto {$codigo}", 'categoria_id' => 1, 'orden' => 'aa', 'precio' => $precio];
        DB::table('productos')->insert([
            $producto(1, '70505', 100),
            $producto(2, 'ABC-1', 200),
            $producto(3, '0094', 300),
            $producto(4, 'IGUAL', 400),
        ]);

        $admin = new Admin(['name' => 'Tester', 'rol' => 'administrador']);
        $admin->id = 1;
        $this->actingAs($admin, 'admin');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storage);
        parent::tearDown();
    }

    /** @param list<array{0: string, 1: mixed}> $filas código (texto) y precio */
    private function subir(array $filas): string
    {
        $libro = new Spreadsheet();
        $hoja = $libro->getActiveSheet();
        $hoja->fromArray(['Código', 'Precio'], null, 'A1');
        foreach ($filas as $i => [$codigo, $precio]) {
            $hoja->setCellValueExplicit('A'.($i + 2), $codigo, DataType::TYPE_STRING);
            $hoja->setCellValue('B'.($i + 2), $precio);
        }
        $ruta = $this->storage.'/lista.xlsx';
        (new Xlsx($libro))->save($ruta);

        $respuesta = $this->from('/dashboard/productos')
            ->post(route('actualizar.precios'), ['lista' => new UploadedFile($ruta, 'Lista octubre.xlsx', null, null, true)])
            ->assertRedirect('/dashboard/productos');

        return (string) $respuesta->getSession()->get('precios_reporte');
    }

    private function precio(int $id): float
    {
        return (float) DB::table('productos')->where('id', $id)->value('precio');
    }

    public function test_actualiza_y_avisa_cuales_codigos_no_se_pudieron_actualizar(): void
    {
        $token = $this->subir([
            ['70505', 150],
            ['abc-1', '2.591,50'],      // sin distinguir mayúsculas; precio con formato argentino
            ['IGUAL', 400],             // ya tenía ese precio: cuenta como actualizado
            ['70.505', 999],            // no existe tal cual: sugiere 70505
            ['94', 999],                // sin los ceros de adelante: sugiere 0094
            ['NOEXISTE', 10],
            ['ABC-1', 'consultar'],
            ['', 50],
        ]);

        $this->assertSame(150.0, $this->precio(1));
        $this->assertSame(2591.5, $this->precio(2));
        $this->assertSame(300.0, $this->precio(3), 'una sugerencia no se aplica sola');

        $reporte = json_decode((string) File::get(storage_path("app/actualizacion-precios/{$token}.json")), true);
        $this->assertSame(['archivo' => 'Lista octubre.xlsx', 'filas' => 8, 'actualizados' => 3, 'sin_cambios' => 1],
            array_intersect_key($reporte, array_flip(['archivo', 'filas', 'actualizados', 'sin_cambios'])));
        $this->assertSame([
            [5, '70.505', 'El código no existe en la web', '70505'],
            [6, '94', 'El código no existe en la web', '0094'],
            [7, 'NOEXISTE', 'El código no existe en la web', null],
            [8, 'ABC-1', 'El precio no es un número válido', null],
            [9, '', 'La fila no tiene código', null],
        ], array_map(fn ($f) => [$f['fila'], $f['codigo'], $f['motivo'], $f['sugerencia']], $reporte['no_actualizados']));

        // El listado muestra el reporte (y no lo pisa el script que restaura filtros).
        $html = $this->withSession(['precios_reporte' => $token])->get('/dashboard/productos')->assertOk()->getContent();
        $this->assertStringContainsString('Se actualizaron 3 productos', $html);
        $this->assertStringContainsString('1 ya tenía ese precio', $html);
        $this->assertStringContainsString('5 filas no se pudieron actualizar', $html);
        $this->assertStringContainsString('NOEXISTE', $html);
        $this->assertStringContainsString(route('actualizar.precios.no-actualizados', $token), $html);
        $this->assertStringNotContainsString('var filtrosGuardados', $html);

        // Y el Excel con los no actualizados.
        $descarga = $this->get(route('actualizar.precios.no-actualizados', $token))->assertOk();
        $this->assertStringContainsString('No actualizados - lista-octubre.xlsx', (string) $descarga->headers->get('content-disposition'));
        $filas = IOFactory::load($descarga->baseResponse->getFile()->getPathname())->getSheet(0)->toArray(null, false, false, false);
        $this->assertSame(['Fila del Excel', 'Código', 'Precio', 'Motivo', 'Código parecido en la web'], $filas[0]);
        $this->assertSame(['70.505', '94', 'NOEXISTE', 'ABC-1', null], array_column(array_slice($filas, 1), 1));
        $this->assertSame('0094', $filas[2][4]);
    }

    public function test_si_todo_se_actualiza_no_muestra_la_lista_de_no_actualizados(): void
    {
        $token = $this->subir([['70505', 1], ['ABC-1', 2]]);

        $html = $this->withSession(['precios_reporte' => $token])->get('/dashboard/productos')->assertOk()->getContent();
        $this->assertStringContainsString('Se actualizaron 2 productos', $html);
        $this->assertStringNotContainsString('no se pudieron actualizar', $html);
    }

    public function test_un_xls_con_texto_mal_codificado_no_rompe_la_lectura(): void
    {
        // Lo que pasó en producción: iconv() avisa «incomplete multibyte
        // character» al leer un comentario del .xls y Laravel lo vuelve error.
        $leer = new \ReflectionMethod(\App\Services\Productos\ActualizacionPrecios::class, 'sinAvisosDeIconv');
        $leer->setAccessible(true);

        $resultado = $leer->invoke(null, fn () => iconv('UTF-8', 'ISO-8859-1', 'texto '.chr(0xC3)) === false ? 'plan B' : 'leído');
        $this->assertSame('plan B', $resultado);

        // Cualquier otro aviso sigue siendo un error.
        $this->expectException(\ErrorException::class);
        $leer->invoke(null, fn () => $noDefinida + 1);
    }

    public function test_un_token_invalido_o_vencido_no_descarga_nada(): void
    {
        $this->get(route('actualizar.precios.no-actualizados', '7f1a0a8e-0000-4000-8000-000000000000'))->assertNotFound();
        $this->get(route('actualizar.precios.no-actualizados', '..%2F..%2F.env'))->assertNotFound();
    }
}
