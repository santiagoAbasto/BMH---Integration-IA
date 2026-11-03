<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Zona de Clientes → Lista de precios → «Lista completa»: todos los productos
 * publicados en un único Excel de una sola hoja. SQLite en memoria.
 */
final class ListaPreciosCompletaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite_memoria',
            'database.connections.sqlite_memoria' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);
        DB::purge('sqlite_memoria');

        Schema::create('users', function (Blueprint $t): void {
            $t->id();
            $t->string('name')->nullable();
            $t->string('rol')->nullable();
            $t->decimal('descuento', 5, 2)->nullable();
            $t->rememberToken();
            $t->timestamps();
        });
        (require base_path('database/migrations/2026_10_19_000001_create_carritos_clientes_table.php'))->up();
        Schema::create('categorias', function (Blueprint $t): void {
            $t->id();
            $t->string('nombre');
        });
        Schema::create('productos', function (Blueprint $t): void {
            $t->id();
            $t->string('codigo');
            $t->string('nombre');
            $t->integer('categoria_id');
            $t->integer('estado')->nullable();
            $t->decimal('precio', 12, 2)->nullable();
            $t->decimal('descuento', 5, 2)->nullable();
        });

        DB::table('categorias')->insert([['id' => 1, 'nombre' => 'Rotores'], ['id' => 2, 'nombre' => 'Arranques']]);
        DB::table('productos')->insert([
            ['codigo' => 'ROT-2', 'nombre' => 'Rotor dos', 'categoria_id' => 1, 'estado' => 2, 'precio' => 2000, 'descuento' => null],
            ['codigo' => 'ROT-1', 'nombre' => 'Rotor uno', 'categoria_id' => 1, 'estado' => 1, 'precio' => 1000, 'descuento' => null],
            ['codigo' => '0094', 'nombre' => 'Arranque', 'categoria_id' => 2, 'estado' => null, 'precio' => 500, 'descuento' => 10],
            ['codigo' => 'OCULTO', 'nombre' => 'No publicado', 'categoria_id' => 2, 'estado' => 0, 'precio' => 99, 'descuento' => null],
        ]);
    }

    /** @return list<list<mixed>> filas de la única hoja */
    private function descargar(float $descuento): array
    {
        $cliente = User::query()->forceCreate(['name' => 'Taller', 'rol' => 'cliente', 'descuento' => $descuento]);

        $respuesta = $this->actingAs($cliente, 'web')->get(route('lista.completa'))->assertOk();
        $this->assertStringContainsString('Lista de precios completa BMH', (string) $respuesta->headers->get('content-disposition'));

        $libro = IOFactory::load($respuesta->baseResponse->getFile()->getPathname());
        $this->assertSame(1, $libro->getSheetCount(), 'una sola hoja, sin solapas por rubro');

        return $libro->getSheet(0)->toArray(null, true, false, false);
    }

    public function test_todos_los_productos_publicados_en_una_hoja_solo_con_el_precio_de_lista(): void
    {
        $filas = $this->descargar(14);

        $this->assertSame(['Código', 'Producto', 'Rubro', 'Estado', 'Precio de lista'], $filas[0]);
        $this->assertCount(4, $filas, 'encabezado y los tres publicados');
        // Por rubro y código; los ocultos no van; el código conserva sus ceros.
        $this->assertSame(['0094', 'ROT-1', 'ROT-2'], array_column(array_slice($filas, 1), 0));
        $this->assertSame(['Arranques', 'Rotores', 'Rotores'], array_column(array_slice($filas, 1), 2));
        $this->assertSame(['', 'Nuevo', 'Reconstruido'], array_map(fn ($v) => (string) $v, array_column(array_slice($filas, 1), 3)));
        $this->assertEqualsWithDelta(500.0, (float) $filas[1][4], 0.001);
        $this->assertEqualsWithDelta(1000.0, (float) $filas[2][4], 0.001);
    }

    public function test_es_la_misma_lista_para_todos_los_clientes(): void
    {
        $this->assertSame($this->descargar(0), $this->descargar(14), 'el descuento del cliente no cambia la lista');
    }

    public function test_un_visitante_no_puede_descargarla(): void
    {
        $this->get(route('lista.completa'))->assertRedirect('/');
    }
}
