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
        // Antes: lista con el 10% del cliente; ahora: oferta con el mismo 10%.
        $this->assertEqualsWithDelta(900.0, $a->precioClienteSinOferta(), 0.001);
        $this->assertEqualsWithDelta(720.0, $a->precio_unitario_descontado(), 0.001);
    }

    public function test_la_tarjeta_del_cliente_muestra_el_precio_de_antes_y_el_de_ahora(): void
    {
        DB::table('productos')->where('id', $this->productos['A'])->update(['precio' => 1000, 'oferta_tipo' => 'porcentaje', 'oferta_valor' => 20]);

        $html = $this->actingAs($this->cliente(), 'web')
            ->get(route('ofertas', ['categoria' => $this->categoria]))->assertOk()->getContent();

        $this->assertStringContainsString('Precio de oferta', $html);
        $this->assertStringContainsString('−20%', $html);
        $this->assertMatchesRegularExpression('#pbmh-oferta-precio__ahora">\$720,00#', $html);
        $this->assertMatchesRegularExpression('#pbmh-oferta-precio__antes">.*\$900,00#s', $html);
        $this->assertStringContainsString('Ahorrás $180,00 por unidad', $html);

        // Zona pública (sin sesión): ni precio de antes y de ahora ni porcentaje; sólo la etiqueta «Oferta».
        auth('web')->logout();
        $publico = $this->get(route('ofertas', ['categoria' => $this->categoria]))->getContent();
        $this->assertStringNotContainsString('Precio de oferta', $publico);
        $this->assertStringNotContainsString('−20%', $publico);
        $this->assertStringNotContainsString('$720,00', $publico);
        $this->assertStringContainsString('pbmh-card--oferta', $publico);
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

    public function test_el_alcance_llega_del_formulario_con_valores_de_antes_y_de_ahora(): void
    {
        $this->assertNull(AlcanceOferta::desdeFormulario(''));
        $this->assertNull(AlcanceOferta::desdeFormulario('0'));
        $this->assertSame(AlcanceOferta::Publica, AlcanceOferta::desdeFormulario('1'));
        $this->assertSame(AlcanceOferta::Clientes, AlcanceOferta::desdeFormulario('clientes'));
        $this->assertNull(AlcanceOferta::desdeFormulario('cualquiera'));
    }
}
