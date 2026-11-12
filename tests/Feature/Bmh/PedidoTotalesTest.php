<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Pedido;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Detalle de un pedido (admin, PDF, mail y Zona de Clientes): el Subtotal y
 * el IVA salían con el descuento de quien miraba el pedido, no con el del
 * cliente. Desde el admin quedaban sin descuento y no cerraban con el TOTAL
 * (pedido 409: subtotal 7.589.201,00 + IVA ≠ 7.530.005,23). SQLite en memoria.
 */
final class PedidoTotalesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
            $t->integer('categoria_id');
            $t->decimal('precio', 12, 2)->nullable();
            $t->decimal('descuento', 5, 2)->nullable();
        });
        Schema::create('pedidos', function (Blueprint $t): void {
            $t->id();
            $t->string('descuento_cliente')->nullable();
            $t->string('total_pedido');
            $t->timestamps();
        });
        Schema::create('pedido_producto', function (Blueprint $t): void {
            $t->id();
            $t->integer('pedido_id');
            $t->integer('producto_id');
            $t->integer('cantidad');
            $t->string('precio_unitario');
            $t->string('precio_descontado');
            $t->string('descuento_producto');
            $t->timestamps();
        });
        Schema::create('impuestos', function (Blueprint $t): void {
            $t->id();
            $t->decimal('porcentaje', 5, 2);
        });
        DB::table('impuestos')->insert(['id' => 1, 'porcentaje' => 21]);
        DB::table('categorias')->insert(['id' => 1, 'nombre' => 'Arranques']);
        DB::table('productos')->insert([
            ['id' => 707, 'codigo' => '1069', 'nombre' => 'ARR. AN 115 LARGA 12V', 'categoria_id' => 1, 'precio' => 61210, 'descuento' => 0],
            ['id' => 1111, 'codigo' => '1502', 'nombre' => 'ARR. FORD F100', 'categoria_id' => 1, 'precio' => 58315, 'descuento' => 0],
        ]);

        // Como lo guarda CarritoController@realizar_pedido: 18% del cliente, precios del momento.
        DB::table('pedidos')->insert(['id' => 409, 'descuento_cliente' => '18', 'total_pedido' => '1.151.046,26']);
        DB::table('pedido_producto')->insert([
            ['pedido_id' => 409, 'producto_id' => 707, 'cantidad' => 18, 'precio_unitario' => '61210', 'precio_descontado' => '0', 'descuento_producto' => '0'],
            ['pedido_id' => 409, 'producto_id' => 1111, 'cantidad' => 1, 'precio_unitario' => '58315', 'precio_descontado' => '0', 'descuento_producto' => '0'],
        ]);
    }

    public function test_desde_el_admin_subtotal_e_iva_llevan_el_descuento_del_cliente_y_cierran_con_el_total(): void
    {
        // Admin: no hay cliente logueado en la web.
        $pedido = Pedido::find(409);

        // (61210 × 18 + 58315) × 0,82 = 951.277,90 → IVA 199.768,36 → total 1.151.046,26
        $this->assertEqualsWithDelta(951277.90, $pedido->subtotal(), 0.001);
        $this->assertSame(199768.36, $pedido->iva());

        $html = view('backend.components.pedido-tabla', ['pedido' => $pedido])->render();
        $this->assertStringContainsString('$ 951.277,90', $html);
        $this->assertStringContainsString('+$199.768,36', $html);
        $this->assertStringContainsString('50.192,20', $html, 'precio neto con el 18%');
        $this->assertStringContainsString('903.459,60', $html, 'subtotal del renglón');
        $this->assertStringContainsString('IVA (21%)', $html);
    }

    public function test_no_depende_de_quien_mira_ni_de_los_precios_de_hoy(): void
    {
        // Un vendedor (u otro cliente) con otro descuento mira el pedido...
        $vendedor = new User(['name' => 'Vendedor']);
        $vendedor->id = 99;
        $vendedor->descuento = 6;
        $this->actingAs($vendedor, 'web');
        // ...y después del pedido se actualizaron los precios.
        DB::table('productos')->update(['precio' => DB::raw('precio * 2')]);

        $pedido = Pedido::find(409);
        $this->assertEqualsWithDelta(951277.90, $pedido->subtotal(), 0.001);

        $linea = $pedido->lineas()->firstWhere('producto.id', 707);
        $this->assertSame(61210.0, $linea->precio_lista);
        $this->assertSame(18.0, $linea->descuento_cliente);
    }

    public function test_un_pedido_viejo_sin_precio_guardado_usa_el_precio_del_producto(): void
    {
        DB::table('pedido_producto')->where('producto_id', 1111)->update(['precio_unitario' => '0']);

        $linea = Pedido::find(409)->lineas()->firstWhere('producto.id', 1111);
        $this->assertSame(58315.0, $linea->precio_lista);
    }
}
