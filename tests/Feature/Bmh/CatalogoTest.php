<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Tests\CreatesApplication;

/**
 * Catálogo con filtros (/productos y /buscar).
 *
 * Igual que SearchNewTablesTest: corre contra la copia local de la legacy,
 * dentro de una categoría de prueba propia para que los conteos sean exactos,
 * y borra todo al terminar.
 */
final class CatalogoTest extends BaseTestCase
{
    use CreatesApplication;

    private int $categoria;

    /** @var list<int> */
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

        // columna_1 es una medida; columna_2 una equivalencia (no se ofrece como medida).
        $this->categoria = DB::table('categorias')->insertGetId([
            'nombre' => 'ZZ CATEGORIA DE PRUEBA '.uniqid(),
            'columna_1' => 'DIAMETRO',
            'columna_2' => 'EQUIVALENCIA NOSSO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4 productos: 3 nuevos y 1 reconstruido; 2 Bosch, 1 Valeo; 2 de Ford.
        $this->crear('A', ['estado' => 1, 'marca' => 'BOSCH', 'columna_1' => '60'], [['FORD (FO)', 'FALCON'], ['FIAT', '147']]);
        $this->crear('B', ['estado' => 1, 'marca' => 'Bosch ', 'columna_1' => '70'], [['FORD', 'F100']]);
        $this->crear('C', ['estado' => 2, 'marca' => 'VALEO', 'columna_1' => '60']);
        $this->crear('D', ['estado' => 1, 'marca' => null, 'columna_1' => '-']);
        // Oculto: nunca se muestra ni se cuenta.
        $this->crear('E', ['estado' => 0, 'marca' => 'BOSCH']);
    }

    protected function tearDown(): void
    {
        DB::table('aplicaciones')->whereIn('producto_id', $this->productos)->delete();
        DB::table('productos')->whereIn('id', $this->productos)->delete();
        DB::table('categorias')->where('id', $this->categoria)->delete();
        config(['database.default' => $this->origDefault, 'database.connections.mysql' => $this->origMysql]);
        DB::purge('mysql');
        parent::tearDown();
    }

    private function crear(string $letra, array $datos, array $aplicaciones = []): int
    {
        $id = DB::table('productos')->insertGetId($datos + [
            'codigo' => 'ZZCAT'.$letra.substr(uniqid(), -5),
            'nombre' => 'PRODUCTO '.$letra,
            'orden' => 'zz',
            'descripcion' => 'test',
            'precio' => 100,
            'categoria_id' => $this->categoria,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($aplicaciones as [$vehiculo, $modelo]) {
            DB::table('aplicaciones')->insert(['producto_id' => $id, 'nombre' => $vehiculo, 'valor' => $modelo, 'orden' => 0]);
        }

        return $this->productos[] = $id;
    }

    /** @return list<string> nombres de los productos listados */
    private function listados(array $query): array
    {
        return $this->get(route('productos', ['categoria' => $this->categoria] + $query))
            ->assertOk()
            ->viewData('productos')
            ->pluck('nombre')
            ->sort()
            ->values()
            ->all();
    }

    public function test_la_categoria_lista_sus_productos_publicados(): void
    {
        $this->assertSame(['PRODUCTO A', 'PRODUCTO B', 'PRODUCTO C', 'PRODUCTO D'], $this->listados([]));
    }

    public function test_filtra_por_estado(): void
    {
        $this->assertSame(['PRODUCTO C'], $this->listados(['estado' => ['reconstruido']]));
        $this->assertSame(['PRODUCTO A', 'PRODUCTO B', 'PRODUCTO D'], $this->listados(['estado' => ['nuevo']]));
    }

    public function test_filtra_por_marca_sin_importar_mayusculas_ni_espacios(): void
    {
        $this->assertSame(['PRODUCTO A', 'PRODUCTO B'], $this->listados(['marca' => 'bosch']));
    }

    public function test_filtra_por_vehiculo_y_modelo_desde_las_aplicaciones(): void
    {
        // «FORD (FO)» y «FORD» son el mismo vehículo.
        $this->assertSame(['PRODUCTO A', 'PRODUCTO B'], $this->listados(['vehiculo' => 'Ford']));
        $this->assertSame(['PRODUCTO B'], $this->listados(['vehiculo' => 'Ford', 'modelo' => 'F100']));
        // Sin vehículo, el modelo solo no filtra.
        $this->assertCount(4, $this->listados(['modelo' => 'F100']));
    }

    public function test_filtra_por_medida_de_la_categoria(): void
    {
        $this->assertSame(['PRODUCTO A', 'PRODUCTO C'], $this->listados(['atributo' => ['columna_1' => '60']]));
    }

    public function test_los_contadores_cuentan_el_resto_de_los_filtros(): void
    {
        $resultado = $this->get(route('productos', ['categoria' => $this->categoria, 'marca' => 'BOSCH']))
            ->viewData('resultado');

        $this->assertSame(2, $resultado->total());
        // Estado: entre los Bosch hay 2 nuevos y ningún reconstruido.
        $this->assertSame([1 => 2], $resultado->estados);
        // Marca: cuenta todas las marcas de la categoría, sin el filtro de marca.
        $this->assertSame(['BOSCH' => 2, 'VALEO' => 1], $resultado->marcas);
        $this->assertSame(['FORD' => 2, 'FIAT' => 1], $resultado->vehiculos);
        $this->assertSame(2, $resultado->categorias[$this->categoria]);
    }

    public function test_ofrece_las_medidas_pero_no_las_equivalencias_ni_los_valores_vacios(): void
    {
        $atributos = $this->get(route('productos', ['categoria' => $this->categoria]))->viewData('resultado')->atributos;

        $this->assertSame(['columna_1'], array_keys($atributos));
        $this->assertSame([60 => 2, 70 => 1], $atributos['columna_1']['opciones']);
    }

    public function test_el_texto_buscado_se_combina_con_los_filtros(): void
    {
        $respuesta = $this->get(route('search', ['q' => 'PRODUCTO', 'categoria' => $this->categoria, 'estado' => ['reconstruido']]))
            ->assertOk();

        $this->assertSame(['PRODUCTO C'], $respuesta->viewData('productos')->pluck('nombre')->all());
    }

    public function test_un_filtro_invalido_se_ignora_en_vez_de_fallar(): void
    {
        $this->assertCount(4, $this->listados(['estado' => ['usado'], 'atributo' => ['drop table' => 'x'], 'paginas' => 'mil']));
    }

    public function test_el_parcial_de_contenido_trae_sidebar_y_resultados(): void
    {
        $this->getJson(route('productos', ['categoria' => $this->categoria, 'estado' => ['nuevo'], 'parcial' => 'contenido']))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonStructure(['html', 'titulo'])
            ->assertSee('data-catalogo-form', false)
            ->assertSee('Quitar filtro Nuevo', false);
    }

    public function test_el_scroll_infinito_pide_de_a_tandas_hasta_el_final(): void
    {
        // Muchos productos de la categoría para tener más de una tanda.
        for ($i = 0; $i < 12; $i++) {
            $this->crear('X'.$i, ['estado' => 1]);
        }

        $pagina = $this->get(route('productos', ['categoria' => $this->categoria]));
        $siguiente = $pagina->viewData('siguiente');
        $this->assertNotNull($siguiente, 'Con 16 productos y tandas de 12 tiene que haber una segunda tanda');
        $this->assertCount(12, $pagina->viewData('productos'));

        $tanda = $this->getJson($siguiente)->assertOk();
        $this->assertSame(2, $tanda->json('pagina'));
        $this->assertNull($tanda->json('siguiente'));
        $this->assertSame(4, substr_count($tanda->json('html'), 'class="producto-cont catalogo__item"'));
        // Las cards que llegan después no repiten el CSS ni el JS de la card.
        $this->assertStringNotContainsString('<style>', $tanda->json('html'));
    }

    public function test_al_volver_muestra_las_tandas_que_ya_se_habian_cargado(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $this->crear('X'.$i, ['estado' => 1]);
        }

        $this->assertCount(16, $this->get(route('productos', ['categoria' => $this->categoria, 'paginas' => 2]))->viewData('productos'));
    }

    public function test_la_zona_de_clientes_usa_el_buscador_y_el_menu_nuevos(): void
    {
        $cliente = new \App\Models\User();
        $cliente->forceFill([
            'name' => 'zztestzona'.uniqid(),
            'email' => 'zz-zona-'.uniqid().'@example.test',
            'rol' => 'cliente',
            'password' => bcrypt(\Illuminate\Support\Str::random(16)),
        ])->save();

        try {
            $this->actingAs($cliente, 'web')
                ->get(route('productos.home'))
                ->assertOk()
                ->assertSee('id="buscador-header"', false)
                ->assertSee('data-menu-productos', false)
                ->assertSee('categorias-grilla', false)
                // El filtro viejo ya no está.
                ->assertDontSee('name="buscadorPrincipal"', false)
                ->assertDontSee('name="codigoBMH"', false);

            // En el catálogo el cliente sigue viendo su navegación.
            $this->actingAs($cliente, 'web')
                ->get(route('productos', ['categoria' => $this->categoria]))
                ->assertOk()
                ->assertSee(route('carrito'), false);
        } finally {
            $cliente->delete();
        }
    }

    public function test_los_links_viejos_de_productos_siguen_andando(): void
    {
        // /productos?categoria=0&search=… era «buscar en todas las categorías».
        $respuesta = $this->get('/productos?categoria=0&search=PRODUCTO%20C')->assertOk();

        $this->assertContains($this->productos[2], $respuesta->viewData('productos')->pluck('id')->all());
    }

    public function test_el_detalle_reutiliza_los_filtros_de_su_categoria_para_visitantes_y_clientes(): void
    {
        $comprobar = function (): void {
            $respuesta = $this->get(route('producto', ['id' => $this->productos[0]]))
                ->assertOk()
                ->assertSee('data-catalogo-detalle', false)
                ->assertSee('data-catalogo-form', false)
                ->assertSee('data-catalogo-abrir-filtros', false)
                ->assertSee('Medidas y características')
                ->assertDontSee('class="filtro2"', false)
                ->assertDontSee('name="buscadorPrincipal"', false)
                ->assertDontSee('name="codigoBMH"', false)
                ->assertDontSee('id="accordionExample"', false);

            $this->assertSame('productos', $respuesta->viewData('ruta'));
            $this->assertSame($this->categoria, $respuesta->viewData('filtros')->categoria);
            $resultado = $respuesta->viewData('resultado');
            $this->assertSame(4, $resultado->total());
            $this->assertSame([1 => 3, 2 => 1], $resultado->estados);
            $this->assertSame([60 => 2, 70 => 1], $resultado->atributos['columna_1']['opciones']);

            // El form va al catálogo, sin arrastrar el id de la ficha.
            $dom = new \DOMDocument();
            @$dom->loadHTML($respuesta->getContent());
            $form = (new \DOMXPath($dom))->query('//form[@data-catalogo-form]')->item(0);
            $this->assertSame(route('productos'), $form->getAttribute('action'));
            $select = (new \DOMXPath($dom))->query('//select[@name="categoria"]/option[@selected]')->item(0);
            $this->assertSame((string) $this->categoria, $select->getAttribute('value'));
        };

        $comprobar();

        $cliente = new \App\Models\User();
        $cliente->forceFill([
            'name' => 'zztestdetalle'.uniqid(),
            'email' => 'zz-detalle-'.uniqid().'@example.test',
            'rol' => 'cliente',
            'password' => bcrypt(\Illuminate\Support\Str::random(16)),
        ])->save();

        try {
            $this->actingAs($cliente, 'web');
            $comprobar();
        } finally {
            $cliente->delete();
        }
    }

    public function test_las_cards_el_detalle_y_sus_partes_comparten_las_fotos_validas_de_cada_producto(): void
    {
        $archivos = ['zz-galeria-'.uniqid().'-a.svg', 'zz-galeria-'.uniqid().'-b.svg'];
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600"><rect width="800" height="600" fill="white"/></svg>';
        $principal = $this->productos[0];
        $parte = $this->productos[1];

        try {
            foreach ($archivos as $archivo) {
                \Illuminate\Support\Facades\File::put(public_path('imagenes/'.$archivo), $svg);
            }
            DB::table('productos')->where('id', $principal)->update(['nombre' => 'PRODUCTO "A" <especial>']);
            foreach ([
                [$principal, 'portada', $archivos[0], 'z'],
                [$principal, 'imagen', $archivos[1], 'a'],
                [$principal, 'imagen', $archivos[1], 'b'], // Duplicada: sólo debe aparecer una vez.
                [$principal, 'imagen', 'zz-galeria-inexistente.webp', 'c'],
                [$parte, 'portada', $archivos[1], 'z'],
                [$parte, 'imagen', $archivos[0], 'a'],
            ] as [$id, $tipo, $path, $orden]) {
                DB::table('imagenes')->insert(['producto_id' => $id, 'sector' => 'producto', 'tipo' => $tipo, 'path' => $path, 'orden' => $orden]);
            }
            DB::table('partes_relacionadas')->insert(['producto_id' => $principal, 'parte_id' => $parte, 'orden' => 0]);

            $urls = array_map(fn ($path) => asset('imagenes/'.$path), $archivos);
            $leer = function (string $html, string $xpath): \DOMElement {
                $dom = new \DOMDocument();
                @$dom->loadHTML($html);

                return (new \DOMXPath($dom))->query($xpath)->item(0);
            };

            $catalogo = $this->get(route('productos', ['categoria' => $this->categoria]))->assertOk();
            $card = $leer($catalogo->getContent(), '//div[@class="pbmh-gallery"]');
            $this->assertSame($urls, json_decode($card->getAttribute('data-gallery-images'), true));
            $this->assertSame('PRODUCTO "A" <especial>', $card->getAttribute('data-gallery-title'));
            $parteCard = $leer($catalogo->getContent(), '//a[@data-gallery-open]');
            $this->assertSame(array_reverse($urls), json_decode($parteCard->getAttribute('data-gallery-images'), true));
            $this->assertSame('PRODUCTO B', $parteCard->getAttribute('data-gallery-title'));

            $detalle = $this->get(route('producto', ['id' => $principal]))->assertOk();
            $galeria = $leer($detalle->getContent(), '//div[@class="fotorama"]');
            $this->assertSame($urls, json_decode($galeria->getAttribute('data-gallery-images'), true));
            $parteDetalle = $leer($detalle->getContent(), '//a[@data-gallery-open]');
            $this->assertSame(array_reverse($urls), json_decode($parteDetalle->getAttribute('data-gallery-images'), true));
            $this->assertSame(1, substr_count($detalle->getContent(), 'id="product-gallery"'));

            $ajax = $this->getJson(route('productos', ['categoria' => $this->categoria, 'parcial' => 'contenido']))->assertOk();
            $cardAjax = $leer($ajax->json('html'), '//div[@class="pbmh-gallery"]');
            $this->assertSame($urls, json_decode($cardAjax->getAttribute('data-gallery-images'), true));
            $this->assertStringNotContainsString('id="product-gallery"', $ajax->json('html'));
        } finally {
            DB::table('partes_relacionadas')->where('producto_id', $principal)->delete();
            DB::table('imagenes')->whereIn('producto_id', [$principal, $parte])->delete();
            foreach ($archivos as $archivo) {
                \Illuminate\Support\Facades\File::delete(public_path('imagenes/'.$archivo));
            }
        }
    }
}
