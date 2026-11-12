<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Actions\Caracteristicas\SincronizarCaracteristicasProducto;
use App\Models\Caracteristica;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\CreatesApplication;

/**
 * Características relacionales: las relaciones de los modelos y el guardado de los valores de un producto
 * (SincronizarCaracteristicasProducto). Base local, con datos propios que se borran al terminar.
 */
final class CaracteristicasModeloTest extends BaseTestCase
{
    use CreatesApplication;
    use UsaBaseLocal;

    private int $categoria;

    private int $producto;

    /** @var array<string, int> */
    private array $caracteristicas = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->usarBaseLocal();

        $sufijo = strtoupper(substr(uniqid(), -6));
        $this->categoria = DB::table('categorias')->insertGetId(['nombre' => 'ZZ CARACT '.$sufijo, 'created_at' => now(), 'updated_at' => now()]);
        $this->producto = DB::table('productos')->insertGetId([
            'codigo' => 'ZZCM'.$sufijo, 'nombre' => 'Producto caract', 'categoria_id' => $this->categoria,
            'orden' => 'zz', 'estado' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ([
            'voltaje' => ['ZZ VOLTAJE '.$sufijo, 'zy', true, true, 'V'],
            'giro' => ['ZZ GIRO '.$sufijo, 'zx', true, false, null],
            'nota' => ['ZZ NOTA '.$sufijo, null, false, false, null],
        ] as $clave => [$nombre, $orden, $mayusculas, $numerica, $unidad]) {
            $this->caracteristicas[$clave] = DB::table('caracteristicas')->insertGetId([
                'nombre' => $nombre, 'orden' => $orden, 'mayusculas' => $mayusculas, 'numerica' => $numerica,
                'unidad' => $unidad, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    protected function tearDown(): void
    {
        DB::table('caracteristica_producto')->where('producto_id', $this->producto)->delete();
        DB::table('caracteristica_categoria')->where('categoria_id', $this->categoria)->delete();
        DB::table('caracteristicas')->whereIn('id', $this->caracteristicas)->delete();
        DB::table('productos')->where('id', $this->producto)->delete();
        DB::table('categorias')->where('id', $this->categoria)->delete();
        $this->restaurarBase();
        parent::tearDown();
    }

    public function test_guarda_los_valores_normalizados_y_quita_los_vacios(): void
    {
        $producto = Producto::findOrFail($this->producto);
        $accion = app(SincronizarCaracteristicasProducto::class);

        $guardado = $accion->ejecutar($producto, [
            $this->caracteristicas['voltaje'] => '12 v',
            $this->caracteristicas['giro'] => 'horario',
            $this->caracteristicas['nota'] => 'Sólo Ford',
        ]);

        $this->assertSame([
            $this->caracteristicas['voltaje'] => '12V',
            $this->caracteristicas['giro'] => 'HORARIO',
            $this->caracteristicas['nota'] => 'Sólo Ford',
        ], $guardado);

        // Se vacía uno y otro no viene: quedan sólo los que tienen valor.
        $accion->ejecutar($producto, [$this->caracteristicas['voltaje'] => '24', $this->caracteristicas['giro'] => ' - ']);

        $this->assertSame(
            [$this->caracteristicas['voltaje'] => '24V'],
            DB::table('caracteristica_producto')->where('producto_id', $this->producto)->pluck('valor', 'caracteristica_id')->all()
        );
    }

    public function test_un_valor_invalido_no_guarda_nada_y_avisa_en_su_campo(): void
    {
        $producto = Producto::findOrFail($this->producto);

        try {
            app(SincronizarCaracteristicasProducto::class)->ejecutar($producto, [
                $this->caracteristicas['voltaje'] => 'doce',
                $this->caracteristicas['giro'] => 'horario',
            ]);
            $this->fail('Tenía que rechazar «doce» en una característica numérica.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('caracteristicas.'.$this->caracteristicas['voltaje'], $e->errors());
        }

        $this->assertSame(0, DB::table('caracteristica_producto')->where('producto_id', $this->producto)->count());
    }

    public function test_relaciones_orden_y_visibles_segun_la_categoria(): void
    {
        DB::table('caracteristica_categoria')->insert([
            ['categoria_id' => $this->categoria, 'caracteristica_id' => $this->caracteristicas['giro']],
            ['categoria_id' => $this->categoria, 'caracteristica_id' => $this->caracteristicas['voltaje']],
        ]);
        $producto = Producto::findOrFail($this->producto);
        app(SincronizarCaracteristicasProducto::class)->ejecutar($producto, [
            $this->caracteristicas['voltaje'] => '12',
            $this->caracteristicas['giro'] => 'horario',
            $this->caracteristicas['nota'] => 'fuera de la categoría',
        ]);

        // Por orden: zx (giro) antes que zy (voltaje); la que no tiene orden, al final.
        $this->assertSame(
            [$this->caracteristicas['giro'], $this->caracteristicas['voltaje'], $this->caracteristicas['nota']],
            $producto->caracteristicas()->pluck('caracteristicas.id')->all()
        );
        $this->assertSame(
            [['nombre' => Caracteristica::find($this->caracteristicas['giro'])->nombre, 'valor' => 'HORARIO'],
                ['nombre' => Caracteristica::find($this->caracteristicas['voltaje'])->nombre, 'valor' => '12V']],
            Producto::with('caracteristicasVisibles')->findOrFail($this->producto)->fichaCaracteristicas()
        );
        $this->assertSame(
            [$this->caracteristicas['giro'], $this->caracteristicas['voltaje']],
            Categoria::findOrFail($this->categoria)->caracteristicas->pluck('id')->all()
        );

        $voltaje = Caracteristica::withCount(['categorias', 'productos'])->findOrFail($this->caracteristicas['voltaje']);
        $this->assertSame(1, $voltaje->categorias_count);
        $this->assertSame(1, $voltaje->productos_count);
        $this->assertSame('numérica · se guarda con V', $voltaje->descripcionFormato());

        $sinUso = Caracteristica::sinUso()->whereIn('id', $this->caracteristicas)->pluck('id')->all();
        $this->assertSame([], $sinUso);
        DB::table('caracteristica_producto')->where('producto_id', $this->producto)->where('caracteristica_id', $this->caracteristicas['nota'])->delete();
        $this->assertSame([$this->caracteristicas['nota']], Caracteristica::sinUso()->whereIn('id', $this->caracteristicas)->pluck('id')->all());
    }

    public function test_nombre_ocupado_no_distingue_tildes_ni_mayusculas_y_orden_siguiente(): void
    {
        $nombre = Caracteristica::find($this->caracteristicas['giro'])->nombre;

        $this->assertNotNull(Caracteristica::nombreOcupado(mb_strtolower($nombre)));
        $this->assertNull(Caracteristica::nombreOcupado($nombre, $this->caracteristicas['giro']));
        $this->assertSame('DIÁMETRO DE POLEA', Caracteristica::normalizarNombre('  diámetro   de polea '));
        $this->assertSame('ab', Caracteristica::normalizarOrden(' AB '));
        $this->assertNull(Caracteristica::normalizarOrden('abc'));
        $this->assertMatchesRegularExpression('/^[0-9a-z]{2}$/', Caracteristica::siguienteOrden());
    }
}
