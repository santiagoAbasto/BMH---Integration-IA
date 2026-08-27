<?php

namespace Tests\Feature\Bmh;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;

class SearchNewTablesTest extends BaseTestCase
{
    use CreatesApplication;

    private array $createdProductoIds = [];

    private string $origDefault = '';
    private array $origMysqlConn = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->origDefault = config('database.default');
        $this->origMysqlConn = config('database.connections.mysql');
        config([
            'database.connections.mysql.database' => 'bmh_legacy',
            'database.connections.mysql.username' => 'bmh_app',
            'database.connections.mysql.password' => 'bmh_app_local',
            'database.default' => 'mysql',
        ]);
        DB::purge('mysql');
    }

    protected function tearDown(): void
    {
        if ($this->createdProductoIds !== []) {
            DB::connection('mysql')->table('partes_relacionadas')
                ->whereIn('producto_id', $this->createdProductoIds)
                ->orWhereIn('parte_id', $this->createdProductoIds)
                ->delete();
            DB::connection('mysql')->table('equivalencias')
                ->whereIn('producto_id', $this->createdProductoIds)->delete();
            DB::connection('mysql')->table('aplicaciones')
                ->whereIn('producto_id', $this->createdProductoIds)->delete();
            DB::connection('mysql')->table('productos')
                ->whereIn('id', $this->createdProductoIds)->delete();
        }
        // Restaurar config para no contaminar otros tests (que esperan bmh_app_test)
        if ($this->origDefault !== '') {
            config(['database.default' => $this->origDefault]);
        }
        if (!empty($this->origMysqlConn)) {
            config(['database.connections.mysql' => $this->origMysqlConn]);
            DB::purge('mysql');
        }
        parent::tearDown();
    }

    private function crearProducto(string $codigo, string $nombre, string $marca = 'TESTMARCA'): int
    {
        $id = DB::connection('mysql')->table('productos')->insertGetId([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'orden' => 'zz',
            'descripcion' => 'test',
            'precio' => 100,
            'marca' => $marca,
            'modelo' => 'TESTMODELO',
            'categoria_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createdProductoIds[] = $id;
        return $id;
    }

    private function assertProductoEnRespuesta(int $productoId, mixed $productosPaginator, string $msg = ''): void
    {
        $collection = null;
        if ($productosPaginator instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $collection = $productosPaginator->getCollection();
        } elseif (is_object($productosPaginator) && method_exists($productosPaginator, 'items')) {
            $collection = collect($productosPaginator->items());
        } else {
            $collection = collect($productosPaginator);
        }
        $ids = $collection->pluck('id')->all();
        $this->assertContains($productoId, $ids, $msg . " Producto {$productoId} no aparecio. IDs: " . implode(',', $ids));
    }

    public function test_general_search_encuentra_por_equivalencia_nueva(): void
    {
        $uniq = 'ZZZEQ' . substr(uniqid(), -6);
        $codigoProd = 'ZZZTESTEQ' . substr(uniqid(), -4);
        $valorEquiv = $uniq;
        $prodId = $this->crearProducto($codigoProd, 'Producto Base Equiv Test');
        DB::connection('mysql')->table('equivalencias')->insert([
            'producto_id' => $prodId,
            'nombre' => 'Bosch',
            'valor' => $valorEquiv,
            'orden' => 0,
        ]);
        $response = $this->get(route('filtroRodamientos', ['buscadorPrincipal' => $valorEquiv]));
        $response->assertStatus(200);
        $productos = $response->viewData('productos');
        $this->assertNotNull($productos, 'La vista debe exponer $productos');
        $this->assertProductoEnRespuesta($prodId, $productos, 'General search deberia encontrar producto via equivalencia nueva. ');
    }

    public function test_general_search_encuentra_por_aplicacion(): void
    {
        $uniq = 'ZZZAPP' . substr(uniqid(), -6);
        $codigoProd = 'ZZZTESTAP' . substr(uniqid(), -4);
        $valorApp = $uniq;
        $prodId = $this->crearProducto($codigoProd, 'Producto Base App Test');
        DB::connection('mysql')->table('aplicaciones')->insert([
            'producto_id' => $prodId,
            'nombre' => 'FORD',
            'valor' => $valorApp,
            'orden' => 0,
        ]);
        $response = $this->get(route('filtroRodamientos', ['buscadorPrincipal' => $valorApp]));
        $response->assertStatus(200);
        $productos = $response->viewData('productos');
        $this->assertProductoEnRespuesta($prodId, $productos, 'General search deberia encontrar producto via aplicacion. ');
    }

    public function test_general_search_encuentra_por_parte_relacionada(): void
    {
        $uniqParte = 'ZZZPARTE' . substr(uniqid(), -6);
        $uniqOwner = 'ZZZOWN' . substr(uniqid(), -4);
        $codigoParte = $uniqParte;
        $codigoOwner = $uniqOwner;
        $parteId = $this->crearProducto($codigoParte, 'Parte Test ' . $uniqParte, 'MARCA_PARTE');
        $ownerId = $this->crearProducto($codigoOwner, 'Owner Test ' . $uniqOwner, 'MARCA_OWNER');
        DB::connection('mysql')->table('partes_relacionadas')->insert([
            'producto_id' => $ownerId,
            'parte_id' => $parteId,
            'orden' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $response = $this->get(route('filtroRodamientos', ['buscadorPrincipal' => $codigoParte]));
        $response->assertStatus(200);
        $productos = $response->viewData('productos');
        $this->assertProductoEnRespuesta($ownerId, $productos, 'General search deberia encontrar owner via parte relacionada. ');
    }

    public function test_equivalencia_filter_encuentra_por_equivalencia_nueva(): void
    {
        $uniq = 'ZZZEQF' . substr(uniqid(), -6);
        $codigoProd = 'ZZZTESTEQF' . substr(uniqid(), -4);
        $valorEquiv = $uniq;
        $prodId = $this->crearProducto($codigoProd, 'Producto Base EquivFiltro Test');
        DB::connection('mysql')->table('equivalencias')->insert([
            'producto_id' => $prodId,
            'nombre' => 'Valeo',
            'valor' => $valorEquiv,
            'orden' => 0,
        ]);
        $response = $this->get(route('filtroRodamientos', ['equivalenciaFiltro' => $valorEquiv]));
        $response->assertStatus(200);
        $productos = $response->viewData('productos');
        $this->assertProductoEnRespuesta($prodId, $productos, 'Equivalencia filter deberia encontrar producto via equivalencia nueva. ');
    }

    public function test_equivalencia_filter_encuentra_por_aplicacion_y_parte(): void
    {
        $uniqApp = 'ZZZAPPF' . substr(uniqid(), -6);
        $codigoProdApp = 'ZZZTESTAPPF' . substr(uniqid(), -4);
        $prodAppId = $this->crearProducto($codigoProdApp, 'Producto Base AppFiltro Test');
        DB::connection('mysql')->table('aplicaciones')->insert([
            'producto_id' => $prodAppId,
            'nombre' => 'TESTAPP',
            'valor' => $uniqApp,
            'orden' => 0,
        ]);
        $responseApp = $this->get(route('filtroRodamientos', ['equivalenciaFiltro' => $uniqApp]));
        $responseApp->assertStatus(200);
        $this->assertProductoEnRespuesta($prodAppId, $responseApp->viewData('productos'), 'Equivalencia filter deberia encontrar via aplicacion. ');
        $uniqParte = 'ZZZPARTEF' . substr(uniqid(), -6);
        $codigoParte = $uniqParte;
        $codigoOwner = 'ZZZOWNF' . substr(uniqid(), -4);
        $parteId = $this->crearProducto($codigoParte, 'Parte EquivFiltro ' . $uniqParte);
        $ownerId = $this->crearProducto($codigoOwner, 'Owner EquivFiltro ' . $uniqParte);
        DB::connection('mysql')->table('partes_relacionadas')->insert([
            'producto_id' => $ownerId,
            'parte_id' => $parteId,
            'orden' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $responseParte = $this->get(route('filtroRodamientos', ['equivalenciaFiltro' => $codigoParte]));
        $responseParte->assertStatus(200);
        $this->assertProductoEnRespuesta($ownerId, $responseParte->viewData('productos'), 'Equivalencia filter deberia encontrar owner via parte. ');
    }

    public function test_legacy_sigue_funcionando(): void
    {
        $uniq = 'ZZZLEG' . substr(uniqid(), -6);
        $codigoProd = 'ZZZTESTLEG' . substr(uniqid(), -4);
        $prodId = $this->crearProducto($codigoProd, 'Nombre Legado ' . $uniq . ' Especial');
        $response = $this->get(route('filtroRodamientos', ['buscadorPrincipal' => $uniq]));
        $response->assertStatus(200);
        $this->assertProductoEnRespuesta($prodId, $response->viewData('productos'), 'Legacy (nombre) debe seguir funcionando. ');
    }
}
