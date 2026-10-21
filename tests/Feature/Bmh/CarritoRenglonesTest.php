<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use CodersFree\Shoppingcart\Facades\Cart;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Sumar, quitar y eliminar un renglón del carrito que ya no está (doble
 * click en el tachito, u otra pestaña u otro dispositivo que ya lo sacó):
 * antes respondía con error 500 y la pantalla quedaba vieja. SQLite en memoria.
 */
final class CarritoRenglonesTest extends TestCase
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
            $t->rememberToken();
            $t->timestamps();
        });
        (require base_path('database/migrations/2026_10_19_000001_create_carritos_clientes_table.php'))->up();

        Route::middleware('web')->post('/_carrito/agregar', function (Request $r) {
            return response()->json(['rowId' => Cart::add($r->id, 'Producto '.$r->id, $r->qty, 100)->rowId]);
        });
    }

    public function test_eliminar_dos_veces_el_mismo_renglon_no_falla(): void
    {
        $rowId = $this->post('/_carrito/agregar', ['id' => 10, 'qty' => 2])->json('rowId');

        $this->postJson(route('carrito.remover'), ['item_id' => $rowId, 'seccion' => 'public'])
            ->assertOk()->assertJsonPath('cart', []);
        // El segundo click (o la otra pestaña): el carrito queda igual y responde bien.
        $this->postJson(route('carrito.remover'), ['item_id' => $rowId, 'seccion' => 'public'])
            ->assertOk()->assertJsonPath('cart', [])->assertJsonStructure(['view', 'cart']);
    }

    public function test_sumar_o_quitar_un_renglon_que_ya_no_esta_devuelve_el_carrito_actual(): void
    {
        $rowId = $this->post('/_carrito/agregar', ['id' => 11, 'qty' => 1])->json('rowId');
        $this->postJson(route('carrito.remover'), ['item_id' => $rowId, 'seccion' => 'public'])->assertOk();

        // La pantalla vieja todavía muestra el renglón y le suman o quitan.
        $this->postJson(route('carrito.sumar'), ['item_id' => $rowId, 'qty' => 1, 'seccion' => 'public'])
            ->assertOk()->assertJsonPath('cart', []);
        $this->postJson(route('carrito.quitar'), ['item_id' => $rowId, 'qty' => 1, 'seccion' => 'public'])
            ->assertOk()->assertJsonPath('cart', []);
    }
}
