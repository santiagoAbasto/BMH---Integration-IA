<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Enums\AlcanceOferta;
use App\Models\Admin;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;

/**
 * Ofertas con alcance: el admin las marca desde el listado (sin oferta / sitio y Zona de Clientes / sólo Zona de
 * Clientes) y /ofertas muestra las que ve cada uno: un visitante, las del sitio; un cliente con sesión, también las
 * «sólo clientes». Como CatalogoTest: base local, con una categoría y productos propios que se borran al terminar.
 */
final class OfertasTest extends BaseTestCase
{
    use CreatesApplication;

    private int $categoria;

    /** @var array<string, int> */
    private array $productos = [];

    private string $origDefault = '';

    private array $origMysql = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->origDefault = config('database.default');
        $this->origMysql = config('database.connections.mysql');
        config([
            'database.connections.mysql.database' => 'bmh_legacy',
            'database.connections.mysql.username' => 'bmh_app',
            'database.connections.mysql.password' => 'bmh_app_local',
            'database.default' => 'mysql',
        ]);
        DB::purge('mysql');

        $this->categoria = DB::table('categorias')->insertGetId([
            'nombre' => 'ZZ OFERTAS DE PRUEBA '.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // A y B: en oferta en el sitio; C: sin oferta; D: sólo para la Zona de Clientes.
        foreach (['A' => 'publica', 'B' => 'publica', 'C' => null, 'D' => 'clientes'] as $letra => $oferta) {
            $this->productos[$letra] = DB::table('productos')->insertGetId([
                'codigo' => 'ZZOF'.$letra.substr(uniqid(), -5),
                'nombre' => 'Producto de oferta '.$letra,
                'categoria_id' => $this->categoria,
                'orden' => 'zz',
                'estado' => 1,
                'oferta' => $oferta,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function tearDown(): void
    {
        DB::table('carritos_clientes')->where('user_id', 999999)->delete();
        DB::table('productos')->whereIn('id', $this->productos)->delete();
        DB::table('categorias')->where('id', $this->categoria)->delete();
        config(['database.default' => $this->origDefault, 'database.connections.mysql' => $this->origMysql]);
        DB::purge('mysql');
        parent::tearDown();
    }

    private function admin(): Admin
    {
        $admin = new Admin(['name' => 'Tester', 'rol' => 'administrador']);
        $admin->id = 1;

        return $admin;
    }

    public function test_el_admin_cambia_el_alcance_de_la_oferta(): void
    {
        $id = $this->productos['C'];
        $cambiar = fn (array $datos) => $this->actingAs($this->admin(), 'admin')
            ->postJson(route('producto.oferta'), ['producto_id' => $id] + $datos)->assertOk();

        $cambiar(['alcance' => 'clientes'])->assertJson(['producto_id' => $id, 'oferta' => true, 'alcance' => 'clientes', 'etiqueta' => 'Sólo Zona de Clientes']);
        $this->assertSame(AlcanceOferta::Clientes, Producto::findOrFail($id)->oferta);

        $cambiar(['alcance' => 'publica'])->assertJson(['alcance' => 'publica']);
        $this->assertSame(AlcanceOferta::Publica, Producto::findOrFail($id)->oferta);

        $cambiar(['alcance' => ''])->assertJson(['oferta' => false, 'alcance' => null]);
        $this->assertNull(Producto::findOrFail($id)->oferta);

        // Compatibilidad: sí/no y sin datos (invierte).
        $cambiar(['oferta' => true])->assertJson(['alcance' => 'publica']);
        $cambiar([])->assertJson(['alcance' => null]);

        $this->actingAs($this->admin(), 'admin')
            ->postJson(route('producto.oferta'), ['producto_id' => $id, 'alcance' => 'cualquiera'])
            ->assertUnprocessable();
    }

    public function test_un_visitante_no_puede_marcar_ofertas(): void
    {
        $this->post(route('producto.oferta'), ['producto_id' => $this->productos['C'], 'alcance' => 'publica']);

        $this->assertNull(Producto::findOrFail($this->productos['C'])->oferta);
    }

    public function test_un_visitante_ve_solo_las_ofertas_del_sitio(): void
    {
        $html = $this->get(route('ofertas', ['categoria' => $this->categoria]))->assertOk()->getContent();

        $this->assertStringContainsString('<h1 class="catalogo__titulo">Ofertas</h1>', $html);
        $this->assertMatchesRegularExpression('#<strong>2</strong>\s*productos\s*en oferta#', $html);
        $this->assertStringContainsString('Producto de oferta A', $html);
        $this->assertStringContainsString('Producto de oferta B', $html);
        $this->assertStringNotContainsString('Producto de oferta C', $html);
        $this->assertStringNotContainsString('Producto de oferta D', $html);
        $this->assertSame(2, substr_count($html, 'pbmh-card--oferta'));
    }

    public function test_un_cliente_ve_tambien_las_ofertas_solo_para_clientes(): void
    {
        $cliente = new User(['name' => 'Cliente', 'rol' => 'cliente']);
        $cliente->id = 999999;

        $html = $this->actingAs($cliente, 'web')->get(route('ofertas', ['categoria' => $this->categoria]))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#<strong>3</strong>\s*productos\s*en oferta#', $html);
        $this->assertStringContainsString('Producto de oferta D', $html);
        $this->assertStringContainsString('Oferta para clientes', $html);
        $this->assertSame(3, substr_count($html, 'pbmh-card--oferta'));
    }

    private function cliente(): User
    {
        $cliente = (new User())->forceFill(['name' => 'Cliente', 'rol' => 'cliente', 'descuento' => 10]);
        $cliente->id = 999999;

        return $cliente;
    }

    public function test_el_precio_de_oferta_se_calcula_con_porcentaje_o_precio_fijo(): void
    {
        DB::table('productos')->where('id', $this->productos['A'])->update(['precio' => 1000, 'oferta_tipo' => 'porcentaje', 'oferta_valor' => 20]);
        DB::table('productos')->where('id', $this->productos['B'])->update(['precio' => 1000, 'oferta_tipo' => 'precio', 'oferta_valor' => 750]);
        $a = Producto::findOrFail($this->productos['A']);
        $b = Producto::findOrFail($this->productos['B']);

        $this->assertSame(800.0, $a->precioOferta());
        $this->assertSame(20, $a->porcentajeOferta());
        $this->assertSame(750.0, $b->precioOferta());
        $this->assertSame(25, $b->porcentajeOferta());

        // Un visitante (y el admin) ve el precio de lista: la oferta sólo cambia el precio para clientes con sesión.
        $this->assertEquals(1000, $a->precio());

        $this->actingAs($this->cliente(), 'web');
        $this->assertEquals(800, $a->precio());
        // El precio de oferta es el final: no se le resta el 10% del cliente (BMH, 18/11/2026).
        $this->assertFalse($a->aplicaDescuentoCliente());
        $this->assertEqualsWithDelta(800.0, $a->precio_unitario_descontado(), 0.001);
        $this->assertEqualsWithDelta(750.0, $b->precio_unitario_descontado(), 0.001);
        // Sin oferta con precio, el 10% del cliente sigue.
        $c = Producto::findOrFail($this->productos['C']);
        DB::table('productos')->where('id', $c->id)->update(['precio' => 1000]);
        $c->refresh();
        $this->assertTrue($c->aplicaDescuentoCliente());
        $this->assertEqualsWithDelta(900.0, $c->precio_unitario_descontado(), 0.001);
    }

    public function test_la_tarjeta_del_cliente_muestra_el_precio_de_antes_y_el_de_ahora(): void
    {
        DB::table('productos')->where('id', $this->productos['A'])->update(['precio' => 1000, 'oferta_tipo' => 'porcentaje', 'oferta_valor' => 20]);

        $html = $this->actingAs($this->cliente(), 'web')
            ->get(route('ofertas', ['categoria' => $this->categoria]))->assertOk()->getContent();

        $this->assertStringContainsString('Precio de oferta', $html);
        $this->assertStringContainsString('−20%', $html);
        // Oferta 800 final (sin el 10% del cliente) y tachado el precio de lista; sin el texto de «Ahorrás».
        $this->assertMatchesRegularExpression('#pbmh-oferta-precio__ahora">\$800,00#', $html);
        $this->assertMatchesRegularExpression('#pbmh-oferta-precio__antes">.*\$1\.000,00#s', $html);
        $this->assertStringNotContainsString('Ahorrás', $html);

        // Zona pública (sin sesión): ni precio de antes y de ahora ni porcentaje; sólo la etiqueta «Oferta».
        auth('web')->logout();
        $publico = $this->get(route('ofertas', ['categoria' => $this->categoria]))->getContent();
        $this->assertStringNotContainsString('Precio de oferta', $publico);
        $this->assertStringNotContainsString('−20%', $publico);
        $this->assertStringNotContainsString('$800,00', $publico);
        $this->assertStringContainsString('pbmh-card--oferta', $publico);
    }

    public function test_el_carrito_cobra_el_precio_de_oferta_sin_el_descuento_del_cliente(): void
    {
        DB::table('productos')->where('id', $this->productos['B'])->update(['precio' => 96759, 'oferta_tipo' => 'precio', 'oferta_valor' => 66000]);
        DB::table('productos')->where('id', $this->productos['C'])->update(['precio' => 1000]);

        $agregar = fn (string $letra) => $this->actingAs($this->cliente(), 'web')
            ->postJson(route('carrito.agregar'), ['producto_id' => $this->productos[$letra], 'qty' => 1])->assertOk()->json();
        $precio = fn (array $carrito, string $letra) => collect($carrito)->firstWhere('id', $this->productos[$letra])['price'];

        $this->assertEquals(66000, $precio($agregar('B'), 'B'));
        $this->assertEquals(900, $precio($agregar('C'), 'C'), 'sin oferta, con el 10% del cliente');
    }

    /**
     * Guardar el producto desde el formulario de edición con una oferta. Daba un error 500: el update seguía escribiendo
     * las columnas fijas de rodamientos (diametroInterno, …) que la migración del 16/11 borró al pasarlas a
     * características.
     */
    public function test_el_admin_guarda_el_producto_con_su_oferta(): void
    {
        $id = $this->productos['C'];
        $actual = DB::table('productos')->where('id', $id)->first();

        $this->actingAs($this->admin(), 'admin')
            ->from('/dashboard/productos/editar?id='.$id)
            ->put(route('producto.update'), [
                'id' => $id,
                'categoria' => $this->categoria,
                'nombre' => 'Producto editado',
                'codigo' => $actual->codigo,
                'orden' => $actual->orden,
                'descripcion' => '<p>Descripción</p>',
                'precio' => '1000',
                'precioN' => '1000',
                'descuento' => '0',
                'marca' => '',
                'modelo' => '',
                'oferta' => 'publica',
                'oferta_tipo' => 'porcentaje',
                'oferta_valor' => '15',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/dashboard/productos/editar?id='.$id)
            ->assertSessionHas('success', 'Producto actualizado');

        $guardado = Producto::findOrFail($id);
        $this->assertSame('Producto editado', $guardado->nombre);
        $this->assertSame(AlcanceOferta::Publica, $guardado->oferta);
        $this->assertSame('porcentaje', $guardado->oferta_tipo);
        $this->assertEquals(15, $guardado->oferta_valor);
    }

    public function test_el_admin_no_puede_cargar_un_descuento_imposible(): void
    {
        DB::table('productos')->where('id', $this->productos['C'])->update(['precio' => 1000]);
        $enviar = fn (array $datos) => $this->actingAs($this->admin(), 'admin')
            ->from('/dashboard/productos/editar?id='.$this->productos['C'])
            ->put(route('producto.update'), ['id' => $this->productos['C'], 'precio' => '1000', 'oferta' => 'publica'] + $datos);

        $enviar(['oferta_tipo' => 'porcentaje', 'oferta_valor' => '100'])->assertSessionHasErrors('oferta_valor');
        $enviar(['oferta_tipo' => 'precio', 'oferta_valor' => '1200'])->assertSessionHasErrors('oferta_valor');
        $enviar(['oferta_tipo' => 'porcentaje', 'oferta_valor' => 'mucho'])->assertSessionHasErrors('oferta_valor');
        $this->assertNull(Producto::findOrFail($this->productos['C'])->oferta);
    }

    public function test_sin_ofertas_se_ve_el_cartel_con_el_boton_a_todos_los_productos(): void
    {
        // Sólo queda D, la oferta «sólo Zona de Clientes»: el visitante ve el cartel; el cliente, la oferta.
        DB::table('productos')->whereIn('id', [$this->productos['A'], $this->productos['B']])->update(['oferta' => null]);
        $url = route('ofertas', ['categoria' => $this->categoria]);

        $visitante = $this->get($url)->assertOk()->getContent();
        $this->assertStringContainsString('No hay ofertas disponibles en', $visitante);
        $this->assertStringContainsString('href="'.route('productos').'"', $visitante);
        $this->assertStringContainsString('Ver todos los productos', $visitante);
        $this->assertStringNotContainsString('pbmh-card--oferta', $visitante, 'no sugiere productos que no están en oferta');
        $this->assertStringNotContainsString('Sin coincidencias exactas', $visitante);

        $cliente = $this->actingAs($this->cliente(), 'web')->get($url)->assertOk()->getContent();
        $this->assertStringNotContainsString('No hay ofertas disponibles', $cliente);
        $this->assertStringContainsString('Producto de oferta D', $cliente);

        // Sin ninguna oferta, el cliente también ve el cartel.
        DB::table('productos')->where('id', $this->productos['D'])->update(['oferta' => null]);
        $cliente = $this->actingAs($this->cliente(), 'web')->get($url)->assertOk()->getContent();
        $this->assertStringContainsString('No hay ofertas disponibles en', $cliente);
        $this->assertStringContainsString('Ver todos los productos', $cliente);
    }

    public function test_el_alcance_llega_del_formulario_con_valores_de_antes_y_de_ahora(): void
    {
        $this->assertNull(AlcanceOferta::desdeFormulario(''));
        $this->assertNull(AlcanceOferta::desdeFormulario('0'));
        $this->assertSame(AlcanceOferta::Publica, AlcanceOferta::desdeFormulario('1'));
        $this->assertSame(AlcanceOferta::Clientes, AlcanceOferta::desdeFormulario('clientes'));
        $this->assertNull(AlcanceOferta::desdeFormulario('cualquiera'));
    }
}
