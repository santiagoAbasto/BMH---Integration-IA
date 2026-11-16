<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Http\Controllers\ProductoController;
use App\Models\Equivalencia;
use App\Models\Producto;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Las equivalencias van siempre en mayúsculas, como las aplicaciones: al
 * guardarlas desde el formulario, al importarlas por Excel y al mostrarlas.
 */
final class EquivalenciasMayusculasTest extends TestCase
{
    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite_memoria',
            'database.connections.sqlite_memoria' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);
        DB::purge('sqlite_memoria');

        Schema::create('productos', function (Blueprint $t): void {
            $t->id();
            $t->string('codigo');
        });
        Schema::create('equivalencias', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('producto_id');
            $t->string('nombre')->nullable();
            $t->string('valor');
            $t->string('orden', 2);
            $t->timestamps();
        });

        DB::table('productos')->insert(['id' => 1, 'codigo' => 'P1']);
        $this->producto = Producto::query()->findOrFail(1);
    }

    private function llamar(string $metodo, mixed ...$argumentos): void
    {
        $m = new ReflectionMethod(ProductoController::class, $metodo);
        $m->setAccessible(true);
        $m->invoke(app(ProductoController::class), ...$argumentos);
    }

    /** @return list<array{0: ?string, 1: string}> */
    private function guardadas(): array
    {
        return DB::table('equivalencias')->orderBy('id')->get(['nombre', 'valor'])
            ->map(fn ($e) => [$e->nombre, $e->valor])->all();
    }

    public function test_el_formulario_las_guarda_en_mayusculas(): void
    {
        $this->llamar('sincronizarEquivalencias', Request::create('/', 'POST', [
            'equivalencias_presente' => '1',
            'equiv_nombre' => ['bosch', ''],
            'equiv_valor' => [' 0986ab1234 ', 'ñandú-x1'],
            'equiv_orden' => ['aa', 'ab'],
        ]), $this->producto);

        $this->assertSame([['BOSCH', '0986AB1234'], [null, 'ÑANDÚ-X1']], $this->guardadas());
    }

    public function test_la_importacion_por_excel_las_guarda_en_mayusculas(): void
    {
        $this->llamar('handleEquivalencias', $this->producto, 'abc-12, Def34 ,abc-12');

        $this->assertSame([[null, 'ABC-12'], [null, 'DEF34']], $this->guardadas());
    }

    public function test_la_ficha_muestra_en_mayusculas_las_que_ya_estaban_cargadas(): void
    {
        $html = view('frontend.components.equivalencias-tabla', ['equivalencias' => [
            new Equivalencia(['nombre' => 'bosch', 'valor' => '0986ab1234', 'orden' => 'aa']),
        ]])->render();

        $this->assertStringContainsString('BOSCH', $html);
        $this->assertStringContainsString('0986AB1234', $html);
        $this->assertStringNotContainsString('0986ab1234', $html);
    }
}
