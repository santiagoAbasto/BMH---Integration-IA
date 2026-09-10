<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use App\Models\Caracteristica;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * El ABM de características del dashboard (/dashboard/caracteristica).
 *
 * Cubre dos reportes del cliente:
 *
 *  1. La página va muy lenta. El controlador traía las 659 características de
 *     una sola vez y la vista dibujaba un modal de edición por fila: ~1,7 MB de
 *     HTML, 660 modales y 1320 formularios. El servidor respondía en 0,1 s; lo
 *     que se arrastraba era el navegador. Se fija acá que la vista pagina, que
 *     no dibuja un modal por registro y que no consulta de más.
 *
 *  2. Borrar varias características tira un error del servidor. `delete()` y
 *     `update()` hacían `Caracteristica::find($request->id)->delete()`: si el id
 *     no existía —doble submit sobre una página pesada, doble clic, o volver
 *     atrás y reintentar sobre una fila ya borrada— `find()` devolvía null y
 *     reventaba con "Call to a member function delete() on null". Con 659 filas
 *     de las cuales sólo 209 nombres son distintos, borrar duplicados en tanda
 *     es justo el flujo que lo dispara.
 *
 * A diferencia del resto de la suite BMH, este test NO lee la copia legacy:
 * necesita escribir, y el ABM no depende de datos reales. Arma su propio
 * esquema en SQLite en memoria —incluidas las dos tablas que consulta el layout
 * del backend— así que corre en cualquier máquina sin infraestructura previa.
 */
final class CaracteristicaDashboardTest extends TestCase
{
    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite_memoria',
            'database.connections.sqlite_memoria' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('sqlite_memoria');

        $this->crearEsquema();

        $this->admin = Admin::query()->create([
            'name' => 'Tester',
            'username' => 'tester',
            'email' => 'tester@example.com',
            'password' => 'x',
            'rol' => 'administrador',
        ]);
    }

    /**
     * El mínimo esquema que necesita la página: las tres tablas del ABM, las dos
     * que consulta `layouts/plantilla-back` y las puntas de las foráneas.
     *
     * Las foráneas van con ON DELETE CASCADE como en MySQL, para que el test de
     * borrado con vínculos verifique el mismo comportamiento que producción.
     */
    private function crearEsquema(): void
    {
        Schema::create('admins', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('username')->nullable();
            $t->string('email')->nullable();
            $t->string('password')->nullable();
            $t->string('rol')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });

        Schema::create('imagenes', function (Blueprint $t) {
            $t->id();
            $t->string('sector')->nullable();
            $t->string('path')->nullable();
            $t->string('posicion')->nullable();
            $t->timestamps();
        });

        Schema::create('pedidos', function (Blueprint $t) {
            $t->id();
            $t->timestamps();
        });

        Schema::create('productos', function (Blueprint $t) {
            $t->id();
            $t->string('nombre')->nullable();
        });

        Schema::create('categorias', function (Blueprint $t) {
            $t->id();
            $t->string('nombre')->nullable();
        });

        Schema::create('caracteristicas', function (Blueprint $t) {
            $t->id();
            $t->string('orden', 55)->nullable();
            $t->string('nombre')->nullable();
            $t->dateTime('created_at')->nullable();
            $t->dateTime('deleted_at')->nullable();
            $t->dateTime('updated_at')->nullable();
        });

        Schema::create('producto_caracteristica', function (Blueprint $t) {
            $t->id();
            $t->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $t->foreignId('caracteristica_id')->constrained('caracteristicas')->cascadeOnDelete();
            $t->string('valor', 50)->nullable();
            $t->dateTime('created_at')->nullable();
            $t->dateTime('deleted_at')->nullable();
        });

        Schema::create('categoria_caracteristica', function (Blueprint $t) {
            $t->id();
            $t->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();
            $t->foreignId('caracteristica_id')->constrained('caracteristicas')->cascadeOnDelete();
            $t->dateTime('created_at')->nullable();
            $t->dateTime('deleted_at')->nullable();
        });

        // El layout del backend hace `Imagen::where('sector','logo2')->first()->path`.
        DB::table('imagenes')->insert(['sector' => 'logo2', 'path' => 'logo2.png']);
    }

    private function nuevaCaracteristica(string $nombre): Caracteristica
    {
        return Caracteristica::query()->create(['nombre' => $nombre]);
    }

    /** Reproduce el catálogo real: cientos de filas con muchos nombres repetidos. */
    private function sembrarCatalogoGrande(int $cantidad = 659): void
    {
        $marcas = ['BOSCH', 'VALEO', 'FIAT', 'FORD', 'DELCO', 'HITACHI', 'ISKRA'];

        $filas = [];
        for ($i = 0; $i < $cantidad; $i++) {
            $filas[] = ['nombre' => $marcas[$i % count($marcas)].' '.$i, 'orden' => null];
        }

        foreach (array_chunk($filas, 200) as $lote) {
            DB::table('caracteristicas')->insert($lote);
        }
    }

    // ---------------------------------------------------------------- borrado

    public function test_borrar_dos_veces_la_misma_caracteristica_no_rompe(): void
    {
        $car = $this->nuevaCaracteristica('BORRADO DOBLE');

        $this->actingAs($this->admin, 'admin')
            ->delete('/dashboard/caracteristicas-delete?id='.$car->id)
            ->assertRedirect();

        // El segundo submit —doble clic, o volver atrás y reintentar— llega con
        // un id que ya no existe. Antes era un 500.
        $this->actingAs($this->admin, 'admin')
            ->delete('/dashboard/caracteristicas-delete?id='.$car->id)
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertNull(Caracteristica::query()->find($car->id));
    }

    public function test_borrar_un_id_inexistente_no_rompe(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->delete('/dashboard/caracteristicas-delete?id=999999999')
            ->assertRedirect()
            ->assertSessionHas('warning');
    }

    public function test_borrar_sin_id_no_rompe(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->delete('/dashboard/caracteristicas-delete')
            ->assertRedirect()
            ->assertSessionHas('warning');
    }

    public function test_borrar_con_un_id_no_numerico_no_rompe(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->delete('/dashboard/caracteristicas-delete?id='.urlencode('1 OR 1=1'))
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertSame(0, Caracteristica::query()->count());
    }

    public function test_borrar_varias_seguidas_las_elimina_a_todas(): void
    {
        $ids = collect(['TANDA A', 'TANDA B', 'TANDA C'])
            ->map(fn (string $nombre) => $this->nuevaCaracteristica($nombre)->id);

        foreach ($ids as $id) {
            $this->actingAs($this->admin, 'admin')
                ->delete('/dashboard/caracteristicas-delete?id='.$id)
                ->assertRedirect()
                ->assertSessionHas('success');
        }

        $this->assertSame(0, Caracteristica::query()->whereIn('id', $ids)->count());
    }

    public function test_borrar_arrastra_los_vinculos_con_productos_y_categorias(): void
    {
        $car = $this->nuevaCaracteristica('BORRADO CON VINCULOS');

        $productoId = DB::table('productos')->insertGetId(['nombre' => 'P']);
        $categoriaId = DB::table('categorias')->insertGetId(['nombre' => 'C']);

        DB::table('producto_caracteristica')->insert([
            'producto_id' => $productoId,
            'caracteristica_id' => $car->id,
            'valor' => 'X',
        ]);
        DB::table('categoria_caracteristica')->insert([
            'categoria_id' => $categoriaId,
            'caracteristica_id' => $car->id,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->delete('/dashboard/caracteristicas-delete?id='.$car->id)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(0, DB::table('producto_caracteristica')->where('caracteristica_id', $car->id)->count());
        $this->assertSame(0, DB::table('categoria_caracteristica')->where('caracteristica_id', $car->id)->count());
    }

    public function test_borrar_vuelve_al_listado_conservando_pagina_y_busqueda(): void
    {
        $car = $this->nuevaCaracteristica('BORRADO CON FILTRO');

        $this->actingAs($this->admin, 'admin')
            ->from('/dashboard/caracteristica?buscar=BORRADO&page=1')
            ->delete('/dashboard/caracteristicas-delete?id='.$car->id)
            ->assertRedirect('/dashboard/caracteristica?buscar=BORRADO&page=1');
    }

    public function test_un_visitante_no_autenticado_no_puede_borrar(): void
    {
        $car = $this->nuevaCaracteristica('NO BORRABLE');

        $this->delete('/dashboard/caracteristicas-delete?id='.$car->id)
            ->assertRedirect('/login/admin');

        $this->assertNotNull(Caracteristica::query()->find($car->id));
    }

    // ------------------------------------------------------------- actualizar

    public function test_actualizar_un_id_inexistente_no_rompe(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->put('/dashboard/caracteristicas-update?id=999999999', ['nombre' => 'LO QUE SEA'])
            ->assertRedirect()
            ->assertSessionHas('warning');
    }

    public function test_actualizar_cambia_el_nombre(): void
    {
        $car = $this->nuevaCaracteristica('NOMBRE VIEJO');

        $this->actingAs($this->admin, 'admin')
            ->put('/dashboard/caracteristicas-update?id='.$car->id, [
                'nombre' => 'NOMBRE NUEVO',
                'orden' => '7',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $car->refresh();
        $this->assertSame('NOMBRE NUEVO', $car->nombre);
        $this->assertSame('7', (string) $car->orden);
    }

    public function test_actualizar_rechaza_un_nombre_vacio(): void
    {
        $car = $this->nuevaCaracteristica('NOMBRE INTACTO');

        $this->actingAs($this->admin, 'admin')
            ->put('/dashboard/caracteristicas-update?id='.$car->id, ['nombre' => '   '])
            ->assertSessionHasErrors('nombre');

        $this->assertSame('NOMBRE INTACTO', $car->refresh()->nombre);
    }

    public function test_actualizar_no_permite_pisar_el_nombre_de_otra(): void
    {
        $ocupada = $this->nuevaCaracteristica('NOMBRE OCUPADO');
        $car = $this->nuevaCaracteristica('NOMBRE A CAMBIAR');

        $this->actingAs($this->admin, 'admin')
            ->put('/dashboard/caracteristicas-update?id='.$car->id, ['nombre' => $ocupada->nombre])
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertSame('NOMBRE A CAMBIAR', $car->refresh()->nombre);
    }

    public function test_actualizar_con_su_propio_nombre_no_se_bloquea_a_si_misma(): void
    {
        $car = $this->nuevaCaracteristica('MISMO NOMBRE');

        $this->actingAs($this->admin, 'admin')
            ->put('/dashboard/caracteristicas-update?id='.$car->id, [
                'nombre' => 'MISMO NOMBRE',
                'orden' => '3',
            ])
            ->assertSessionHas('success');

        $this->assertSame('3', (string) $car->refresh()->orden);
    }

    // ------------------------------------------------------------------ alta

    public function test_crear_rechaza_un_nombre_vacio(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post('/dashboard/caracteristicas-store', ['nombre' => '  '])
            ->assertSessionHasErrors('nombre');

        $this->assertSame(0, Caracteristica::query()->count());
    }

    public function test_crear_recorta_los_espacios_y_evita_duplicados(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post('/dashboard/caracteristicas-store', ['nombre' => '  ALTA UNICA  '])
            ->assertSessionHas('success');

        $this->assertSame(1, Caracteristica::query()->where('nombre', 'ALTA UNICA')->count());

        $this->actingAs($this->admin, 'admin')
            ->post('/dashboard/caracteristicas-store', ['nombre' => 'ALTA UNICA'])
            ->assertSessionHas('warning');

        $this->assertSame(1, Caracteristica::query()->where('nombre', 'ALTA UNICA')->count());
    }

    // --------------------------------------------------------------- listado

    public function test_el_listado_pagina_y_no_dibuja_un_modal_por_fila(): void
    {
        $this->sembrarCatalogoGrande();

        $response = $this->actingAs($this->admin, 'admin')->get('/dashboard/caracteristica');
        $response->assertOk();

        $html = $response->getContent();

        // El síntoma que reportó el cliente: la página pesaba ~1,7 MB.
        $this->assertLessThan(
            600 * 1024,
            strlen($html),
            'El listado no debería devolver más de 600 KB de HTML.'
        );

        // Un modal de edición compartido, no uno por registro.
        $this->assertLessThan(
            5,
            substr_count($html, 'modal fade'),
            'La vista no debería dibujar un modal por fila.'
        );

        // Y una porción del catálogo, no las 659 filas.
        $this->assertLessThan(
            100,
            substr_count($html, 'data-caracteristica-id'),
            'El listado debería estar paginado.'
        );
    }

    public function test_la_segunda_pagina_trae_registros_distintos(): void
    {
        $this->sembrarCatalogoGrande();

        $primera = $this->actingAs($this->admin, 'admin')
            ->get('/dashboard/caracteristica')->assertOk()->getContent();
        $segunda = $this->actingAs($this->admin, 'admin')
            ->get('/dashboard/caracteristica?page=2')->assertOk()->getContent();

        $this->assertNotSame(
            $this->idsListados($primera),
            $this->idsListados($segunda),
            'La página 2 debería traer otras características.'
        );
        $this->assertNotEmpty($this->idsListados($segunda));
    }

    public function test_el_listado_filtra_por_nombre(): void
    {
        $this->sembrarCatalogoGrande();
        $this->nuevaCaracteristica('FILTRO ZZZ');

        $response = $this->actingAs($this->admin, 'admin')
            ->get('/dashboard/caracteristica?buscar=FILTRO+ZZZ');

        $response->assertOk();
        $response->assertSee('FILTRO ZZZ', false);
        $response->assertDontSee('BOSCH', false);
    }

    public function test_la_busqueda_sobrevive_al_paginado(): void
    {
        $this->sembrarCatalogoGrande();

        $html = $this->actingAs($this->admin, 'admin')
            ->get('/dashboard/caracteristica?buscar=BOSCH')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('buscar=BOSCH', $html, 'Los links del paginador deberían arrastrar el filtro.');
    }

    public function test_el_listado_no_dispara_una_consulta_por_fila(): void
    {
        $this->sembrarCatalogoGrande();

        DB::enableQueryLog();

        $this->actingAs($this->admin, 'admin')->get('/dashboard/caracteristica')->assertOk();

        $queries = collect(DB::getQueryLog())
            ->filter(fn (array $q) => str_contains($q['query'], 'caracteristicas'));

        DB::disableQueryLog();

        // Una para la página de resultados y otra para el total de la paginación.
        $this->assertLessThanOrEqual(
            2,
            $queries->count(),
            'El listado no debería consultar caracteristicas más de dos veces, y trajo: '
                .$queries->pluck('query')->implode(' | ')
        );
    }

    // ------------------------------------------------------------------ orden

    /**
     * Cuatro filas cuyo orden cambia según el criterio: dos con `orden`
     * cargado (a1 < b1) y dos sin él, que van al final entre sí por nombre.
     *
     * @return array<string, int> nombre => id
     */
    private function sembrarParaOrdenar(): array
    {
        $ids = [];
        foreach ([['CHARLY', null], ['ALFA', 'b1'], ['DELTA', 'a1'], ['BRAVO', null]] as [$nombre, $orden]) {
            $ids[$nombre] = DB::table('caracteristicas')->insertGetId(['nombre' => $nombre, 'orden' => $orden]);
        }

        return $ids;
    }

    /**
     * @param  array<string, int>  $ids
     * @return list<string> nombres en el orden en que los muestra el listado
     */
    private function ordenListado(string $query, array $ids): array
    {
        $html = $this->actingAs($this->admin, 'admin')
            ->get('/dashboard/caracteristica'.$query)
            ->assertOk()
            ->getContent();

        $nombrePorId = array_flip($ids);

        return array_map(fn (string $id) => $nombrePorId[(int) $id], $this->idsListados($html));
    }

    public function test_ordena_por_nombre_ascendente(): void
    {
        $ids = $this->sembrarParaOrdenar();

        $this->assertSame(['ALFA', 'BRAVO', 'CHARLY', 'DELTA'], $this->ordenListado('?ordenar=az', $ids));
    }

    public function test_ordena_por_nombre_descendente(): void
    {
        $ids = $this->sembrarParaOrdenar();

        $this->assertSame(['DELTA', 'CHARLY', 'BRAVO', 'ALFA'], $this->ordenListado('?ordenar=za', $ids));
    }

    public function test_por_defecto_ordena_por_campo_orden_con_los_vacios_al_final(): void
    {
        $ids = $this->sembrarParaOrdenar();
        $esperado = ['DELTA', 'ALFA', 'BRAVO', 'CHARLY'];

        $this->assertSame($esperado, $this->ordenListado('', $ids));
        $this->assertSame($esperado, $this->ordenListado('?ordenar=orden', $ids));
    }

    public function test_un_criterio_invalido_usa_el_orden_por_defecto(): void
    {
        $ids = $this->sembrarParaOrdenar();

        $this->assertSame(
            ['DELTA', 'ALFA', 'BRAVO', 'CHARLY'],
            $this->ordenListado('?ordenar='.urlencode('nombre desc; DROP TABLE caracteristicas'), $ids)
        );
    }

    public function test_el_orden_se_combina_con_la_busqueda_y_sobrevive_al_paginado(): void
    {
        $this->sembrarCatalogoGrande();

        $html = $this->actingAs($this->admin, 'admin')
            ->get('/dashboard/caracteristica?buscar=BOSCH&ordenar=za')
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/href="[^"]*buscar=BOSCH[^"]*ordenar=za[^"]*page=2"/',
            $html,
            'El link a la página 2 debería arrastrar la búsqueda y el orden.'
        );
    }

    public function test_el_selector_marca_el_criterio_activo(): void
    {
        $html = $this->actingAs($this->admin, 'admin')
            ->get('/dashboard/caracteristica?ordenar=za')
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/<option value="za"\s+selected/', $html);
        $this->assertDoesNotMatchRegularExpression('/<option value="az"\s+selected/', $html);
    }

    /** @return list<string> */
    private function idsListados(string $html): array
    {
        preg_match_all('/data-caracteristica-id="(\d+)"/', $html, $m);

        return array_values(array_unique($m[1]));
    }
}
