<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class ProductosDashboardBusquedaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite_memoria',
            'database.connections.sqlite_memoria' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);
        DB::purge('sqlite_memoria');

        Schema::create('categorias', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
        });
        Schema::create('productos', function (Blueprint $t) {
            $t->id();
            $t->string('codigo');
            $t->string('nombre');
            $t->text('descripcion')->nullable();
            $t->integer('categoria_id');
            $t->string('orden');
            $t->boolean('destacada')->default(false);
        });
        Schema::create('imagenes', function (Blueprint $t) {
            $t->id();
            $t->string('sector');
            $t->string('tipo')->nullable();
            $t->string('path')->nullable();
            $t->integer('producto_id')->nullable();
        });
        Schema::create('pedidos', function (Blueprint $t) {
            $t->id();
            $t->timestamps();
        });
        DB::table('imagenes')->insert(['sector' => 'logo2', 'path' => 'logo2.png']);

        DB::table('categorias')->insert([
            ['id' => 1, 'nombre' => 'Repuestos'],
            ['id' => 2, 'nombre' => 'Categoría 0011'],
            ['id' => 3, 'nombre' => 'Sin coincidencias'],
        ]);

        // El guard recibe un admin en memoria: el test no toca bases reales.
        $admin = new Admin(['name' => 'Tester', 'rol' => 'administrador']);
        $admin->id = 1;
        $this->actingAs($admin, 'admin');
    }

    private function producto(string $codigo, string $orden, array $datos = []): void
    {
        DB::table('productos')->insert([
            'codigo' => $codigo,
            'nombre' => 'Repuesto',
            'categoria_id' => 1,
            'orden' => $orden,
            ...$datos,
        ]);
    }

    private function buscar(string $valor = '0011', int|string $categoria = 'todos', int $pagina = 1): TestResponse
    {
        return $this->postJson('/dashboard/buscar-producto', [
            'valor' => $valor,
            'categoria_id' => $categoria,
            'page' => $pagina,
        ])->assertOk();
    }

    /** @return list<string> */
    private function codigos(TestResponse $respuesta): array
    {
        preg_match_all('/data-producto-codigo="([^"]*)"/', $respuesta->json('html'), $matches);

        return array_map(fn (string $codigo): string => html_entity_decode($codigo, ENT_QUOTES, 'UTF-8'), $matches[1]);
    }

    public function test_prioriza_codigo_exacto_prefijo_y_parcial_sobre_los_otros_campos(): void
    {
        $this->producto('NOMBRE', 'a', ['nombre' => 'Repuesto 0011']);
        $this->producto('DESCRIPCION', 'b', ['descripcion' => 'Modelo 0011']);
        $this->producto('CATEGORIA', 'c', ['categoria_id' => 2]);
        $this->producto('20011', 'd');
        $this->producto('00112', 'e');
        $this->producto('0011', 'z');
        $this->producto('SIN-MATCH', '0');

        $this->assertSame(
            ['0011', '00112', '20011', 'NOMBRE', 'DESCRIPCION', 'CATEGORIA'],
            $this->codigos($this->buscar())
        );
    }

    public function test_el_codigo_exacto_aparece_en_la_primera_pagina(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->producto('20011-'.$i, 'a');
        }
        $this->producto('0011', 'z');

        $primera = $this->codigos($this->buscar());
        $segunda = $this->codigos($this->buscar(pagina: 2));

        $this->assertCount(20, $primera);
        $this->assertSame('0011', $primera[0]);
        $this->assertCount(6, $segunda);
        $this->assertNotContains('0011', $segunda);
    }

    public function test_respeta_la_categoria_y_ordena_tambien_las_alternativas(): void
    {
        $this->producto('20011', 'a');
        $this->producto('0011', 'z');
        $this->producto('0011-OTRA', 'a', ['categoria_id' => 2]);

        $filtrada = $this->buscar(categoria: 1);
        $this->assertSame(['0011', '20011'], $this->codigos($filtrada));
        $filtrada->assertJsonPath('sinResultadosCategoria', false);

        $alternativas = $this->buscar(categoria: 3);
        $this->assertSame(['0011', '0011-OTRA', '20011'], $this->codigos($alternativas));
        $alternativas->assertJsonPath('sinResultadosCategoria', true)
            ->assertJsonPath('hayAlternativas', true);
    }

    public function test_normaliza_mayusculas_y_espacios_sin_perder_ceros_iniciales(): void
    {
        $this->producto('XAB0011', 'a');
        $this->producto('AB0011', 'z');
        $this->producto('AB11', 'b');

        $this->assertSame(['AB0011', 'XAB0011'], $this->codigos($this->buscar('  ab0011  ')));
    }

    public function test_sin_busqueda_conserva_el_orden_manual(): void
    {
        $this->producto('0011', 'z');
        $this->producto('20011', 'a');

        $this->assertSame(['20011', '0011'], $this->codigos($this->buscar('')));
    }

    public function test_la_pagina_usa_una_sola_lista_responsive_y_controles_etiquetados(): void
    {
        $this->producto('0011', 'a');
        $html = $this->get('/dashboard/productos')->assertOk()->getContent();

        $this->assertStringContainsString('class="productos-dashboard"', $html);
        $this->assertStringContainsString('css/dashboard-productos.css', $html);
        $this->assertStringContainsString('for="productos-busqueda"', $html);
        $this->assertStringContainsString('aria-label="Editar producto 0011"', $html);
        $this->assertStringContainsString('aria-label="Eliminar producto 0011"', $html);
        $this->assertSame(1, substr_count($html, 'data-producto-codigo="0011"'));
        $this->assertStringNotContainsString('cdn.tailwindcss.com', $html);
        $this->assertDoesNotMatchRegularExpression('/<nav id="sidebar"[^>]*class="[^"]*\bshow\b/', $html);

        preg_match('/<section class="productos-page".*?<\/section>/s', $html, $pagina);
        $this->assertDoesNotMatchRegularExpression('/<a\b[^>]*>\s*<button\b/', $pagina[0]);
        preg_match_all('/\bid="([^"]+)"/', $pagina[0], $ids);
        $this->assertSame($ids[1], array_values(array_unique($ids[1])), 'No debe haber IDs duplicados.');
    }

    public function test_el_paginado_inicial_y_ajax_usan_bootstrap_sin_depender_de_tailwind(): void
    {
        for ($i = 0; $i < 25; $i++) {
            $this->producto('0011-'.$i, 'a');
        }

        $html = $this->get('/dashboard/productos')->assertOk()->getContent();
        $this->assertStringContainsString('class="page-link"', $html);
        $this->assertStringContainsString('class="page-link"', $this->buscar()->json('pagination'));
    }

    public function test_la_busqueda_vacia_muestra_un_estado_sin_resultados(): void
    {
        $respuesta = $this->buscar('inexistente');
        $this->assertStringContainsString('No hay productos para mostrar', $respuesta->json('html'));
        $this->assertSame([], $this->codigos($respuesta));
    }

    public function test_el_listado_no_hace_consultas_por_cada_producto(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->producto('0011-'.$i, 'a');
        }

        DB::enableQueryLog();
        $this->buscar();
        $consultas = collect(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(1, $consultas->filter(fn (array $q): bool => str_contains($q['query'], 'from "imagenes"')));
        $this->assertCount(1, $consultas->filter(fn (array $q): bool => str_contains($q['query'], 'from "categorias"') && !str_contains($q['query'], 'from "productos"')));
    }
}
