<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\User;
use CodersFree\Shoppingcart\Facades\Cart;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * El carrito del cliente en la base: el mismo en todos sus dispositivos.
 * Cada «dispositivo» es una sesión distinta (flushSession). Hermético:
 * SQLite en memoria y rutas de prueba con el middleware web real.
 */
final class CarritoCompartidoTest extends TestCase
{
    private User $cliente;

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
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->string('rol')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });
        (require base_path('database/migrations/2026_10_19_000001_create_carritos_clientes_table.php'))->up();
        Schema::create('productos', function (Blueprint $t): void {
            $t->id();
            $t->string('nombre')->nullable();
        });
        // Los productos que usan los tests (el carrito saca los que no existen).
        DB::table('productos')->insert(array_map(fn (int $id) => ['id' => $id, 'nombre' => 'Producto '.$id], range(1, 100)));

        $this->cliente = User::query()->forceCreate(['name' => 'Cliente', 'email' => 'c@x.com', 'rol' => 'cliente']);

        Route::middleware('web')->group(function (): void {
            Route::get('/_carrito', fn () => response()->json(Cart::content()->mapWithKeys(fn ($i) => [$i->id => $i->qty])));
            Route::post('/_carrito/agregar', fn (Request $r) => tap(response('ok'), fn () => Cart::add($r->id, 'Producto '.$r->id, $r->qty, 100)));
            Route::post('/_carrito/quitar', function (Request $r) {
                $item = Cart::content()->firstWhere('id', $r->id);
                Cart::remove($item->rowId);

                return response('ok');
            });
            Route::post('/_carrito/vaciar', fn () => tap(response('ok'), fn () => Cart::destroy()));
            Route::post('/_ingresar', fn (Request $r) => tap(response('ok'), fn () => Auth::guard('web')->login(User::find($r->id))));
            Route::post('/_salir', fn () => tap(response('ok'), fn () => Auth::guard('web')->logout()));
        });
    }

    /** @return array<string, int> */
    private function carrito(): array
    {
        return $this->getJson('/_carrito')->assertOk()->json();
    }

    private function otroDispositivo(?User $usuario = null): void
    {
        $this->flushSession();
        Auth::guard('web')->logout();
        $this->actingAs($usuario ?? $this->cliente, 'web');
    }

    public function test_lo_que_agrega_en_un_dispositivo_lo_ve_en_otro(): void
    {
        $this->actingAs($this->cliente, 'web');
        $this->post('/_carrito/agregar', ['id' => 10, 'qty' => 2])->assertOk();
        $this->post('/_carrito/agregar', ['id' => 11, 'qty' => 1])->assertOk();

        $this->otroDispositivo();

        $this->assertEquals([10 => 2, 11 => 1], $this->carrito());
    }

    public function test_lo_que_saca_en_un_dispositivo_no_reaparece_en_el_otro(): void
    {
        $this->actingAs($this->cliente, 'web');
        $this->post('/_carrito/agregar', ['id' => 10, 'qty' => 2]);
        $this->post('/_carrito/agregar', ['id' => 11, 'qty' => 1]);

        $this->otroDispositivo();
        $this->post('/_carrito/quitar', ['id' => 10])->assertOk();

        $this->otroDispositivo();
        $this->assertEquals([11 => 1], $this->carrito());
    }

    public function test_vaciar_el_carrito_lo_vacia_en_todos_los_dispositivos(): void
    {
        $this->actingAs($this->cliente, 'web');
        $this->post('/_carrito/agregar', ['id' => 10, 'qty' => 2]);
        $this->post('/_carrito/vaciar')->assertOk();

        $this->otroDispositivo();
        $this->assertEquals([], $this->carrito());
    }

    public function test_el_carrito_que_ya_estaba_en_la_sesion_se_guarda_y_no_se_pierde(): void
    {
        // Como un cliente que tenía el carrito armado antes de que existiera esto.
        $this->actingAs($this->cliente, 'web');
        $this->withSession([]);
        Cart::add(30, 'Producto 30', 4, 100);

        $this->assertEquals([30 => 4], $this->carrito());
        $this->assertSame(1, DB::table('carritos_clientes')->where('user_id', $this->cliente->id)->count());

        $this->otroDispositivo();
        $this->assertEquals([30 => 4], $this->carrito());
    }

    public function test_al_ingresar_se_une_lo_que_agrego_como_invitado(): void
    {
        // Guardado de antes: 10 x2 y 20 x3.
        $this->actingAs($this->cliente, 'web');
        $this->post('/_carrito/agregar', ['id' => 10, 'qty' => 2]);
        $this->post('/_carrito/agregar', ['id' => 20, 'qty' => 3]);

        // Otro navegador, sin loguear: agrega 20 x1 y 40 x5.
        $this->flushSession();
        Auth::guard('web')->logout();
        $this->post('/_carrito/agregar', ['id' => 20, 'qty' => 1]);
        $this->post('/_carrito/agregar', ['id' => 40, 'qty' => 5]);
        $this->assertSame(1, DB::table('carritos_clientes')->count(), 'el invitado no toca la base');

        $this->post('/_ingresar', ['id' => $this->cliente->id])->assertOk();

        // Mismo producto en los dos: la cantidad mayor, no la suma.
        $this->assertEquals([10 => 2, 20 => 3, 40 => 5], $this->carrito());
        $this->otroDispositivo();
        $this->assertEquals([10 => 2, 20 => 3, 40 => 5], $this->carrito());
    }

    public function test_al_salir_el_carrito_no_queda_para_el_proximo_que_entra_en_ese_navegador(): void
    {
        $otro = User::query()->forceCreate(['name' => 'Vendedor', 'email' => 'v@x.com', 'rol' => 'vendedor']);

        // Mismo navegador: entra el cliente, agrega y sale.
        $this->post('/_ingresar', ['id' => $this->cliente->id]);
        $this->post('/_carrito/agregar', ['id' => 10, 'qty' => 2]);
        $this->post('/_salir')->assertOk();
        $this->assertEquals([], $this->carrito(), 'como invitado ya no ve el carrito del cliente');

        // Entra otra persona en esa compu: no se lleva lo del cliente.
        $this->post('/_ingresar', ['id' => $otro->id]);
        $this->assertEquals([], $this->carrito());
        $this->post('/_salir');

        // El cliente lo recupera al volver a entrar.
        $this->post('/_ingresar', ['id' => $this->cliente->id]);
        $this->assertEquals([10 => 2], $this->carrito());
    }

    public function test_si_la_tabla_no_esta_el_carrito_sigue_andando_en_la_sesion(): void
    {
        Schema::drop('carritos_clientes');

        $this->post('/_ingresar', ['id' => $this->cliente->id])->assertOk();
        $this->post('/_carrito/agregar', ['id' => 10, 'qty' => 2])->assertOk();

        $this->assertEquals([10 => 2], $this->carrito());
    }

    public function test_un_producto_borrado_del_admin_sale_del_carrito_guardado(): void
    {
        // Antes rompía la página del carrito y el pedido en todos sus dispositivos.
        $this->actingAs($this->cliente, 'web');
        $this->post('/_carrito/agregar', ['id' => 10, 'qty' => 2]);
        $this->post('/_carrito/agregar', ['id' => 11, 'qty' => 1]);

        DB::table('productos')->where('id', 11)->delete();

        $this->otroDispositivo();
        $this->assertEquals([10 => 2], $this->carrito());
        $guardado = json_decode((string) DB::table('carritos_clientes')->where('user_id', $this->cliente->id)->value('contenido'), true);
        $this->assertSame([10], array_column($guardado, 'id'), 'también sale del carrito guardado');
    }

    public function test_cada_cliente_tiene_su_carrito(): void
    {
        $otro = User::query()->forceCreate(['name' => 'Otro', 'email' => 'o@x.com', 'rol' => 'cliente']);

        $this->actingAs($this->cliente, 'web');
        $this->post('/_carrito/agregar', ['id' => 10, 'qty' => 2]);

        $this->otroDispositivo($otro);
        $this->assertEquals([], $this->carrito());
        $this->post('/_carrito/agregar', ['id' => 50, 'qty' => 1]);

        $this->otroDispositivo();
        $this->assertEquals([10 => 2], $this->carrito());
    }
}
