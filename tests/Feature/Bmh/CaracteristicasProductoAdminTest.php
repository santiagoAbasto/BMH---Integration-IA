<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Crear y editar un producto en el admin: los valores de las características se guardan en
 * `caracteristica_producto` con el formato de cada una (antes UnidadesProductoTest, cuando la regla de
 * voltaje y diámetro estaba escrita en el código y los valores en las columnas viejas).
 *
 * Hermético: SQLite en memoria con el mínimo esquema.
 */
final class CaracteristicasProductoAdminTest extends TestCase
{
    private const VOLTAJE = 1;

    private const DIAMETRO = 2;

    private const MARCA = 3;

    private const NOTA = 4;

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
            $t->timestamps();
        });
        Schema::create('caracteristicas', function (Blueprint $t) {
            $t->id();
            $t->string('orden', 2)->nullable();
            $t->string('nombre');
            $t->boolean('mayusculas')->default(true);
            $t->boolean('numerica')->default(false);
            $t->string('unidad', 10)->nullable();
            $t->timestamps();
        });
        Schema::create('productos', function (Blueprint $t) {
            $t->id();
            $t->integer('categoria_id')->nullable();
            foreach ([
                'codigo', 'nombre', 'orden', 'descripcion', 'precio', 'precioN', 'descuento',
                'diametroInterno', 'diametroExterno', 'anchoBanda', 'tolerancia', 'blindaje',
                'marca', 'modelo', 'estado', 'orden_equivalencias', 'orden_aplicaciones', 'orden_partes',
            ] as $campo) {
                $t->string($campo)->nullable();
            }
            $t->boolean('oferta')->default(false);
            $t->timestamps();
        });
        Schema::create('caracteristica_producto', function (Blueprint $t) {
            $t->integer('caracteristica_id');
            $t->integer('producto_id');
            $t->string('valor');
            $t->timestamps();
            $t->primary(['producto_id', 'caracteristica_id']);
        });
        Schema::create('caracteristica_categoria', function (Blueprint $t) {
            $t->integer('caracteristica_id');
            $t->integer('categoria_id');
            $t->timestamps();
            $t->primary(['categoria_id', 'caracteristica_id']);
        });

        DB::table('categorias')->insert(['id' => 1, 'nombre' => 'Alternadores']);
        DB::table('caracteristicas')->insert([
            ['id' => self::VOLTAJE, 'orden' => 'aa', 'nombre' => 'VOLTAJE', 'mayusculas' => true, 'numerica' => true, 'unidad' => 'V'],
            ['id' => self::DIAMETRO, 'orden' => 'ab', 'nombre' => 'DIÁMETRO', 'mayusculas' => true, 'numerica' => true, 'unidad' => 'MM'],
            ['id' => self::MARCA, 'orden' => 'ac', 'nombre' => 'MARCA', 'mayusculas' => true, 'numerica' => false, 'unidad' => null],
            ['id' => self::NOTA, 'orden' => 'ad', 'nombre' => 'NOTA', 'mayusculas' => false, 'numerica' => false, 'unidad' => null],
        ]);
        DB::table('caracteristica_categoria')->insert([
            ['categoria_id' => 1, 'caracteristica_id' => self::VOLTAJE],
            ['categoria_id' => 1, 'caracteristica_id' => self::DIAMETRO],
        ]);

        $admin = new Admin(['name' => 'Tester', 'rol' => 'administrador']);
        $admin->id = 1;
        $this->actingAs($admin, 'admin');
    }

    /** @param  array<int, mixed>  $caracteristicas */
    private function datos(array $caracteristicas): array
    {
        return [
            'nombre' => 'Alternador',
            'codigo' => 'ALT-1',
            'orden' => 'aa',
            'categoria' => 1,
            'caracteristicas_presente' => 1,
            'caracteristicas' => $caracteristicas,
        ];
    }

    /** @return array<int, string> caracteristica_id => valor guardado */
    private function guardados(int $productoId): array
    {
        return DB::table('caracteristica_producto')->where('producto_id', $productoId)
            ->orderBy('caracteristica_id')->pluck('valor', 'caracteristica_id')->all();
    }

    /** @return array<string, array{int, mixed, ?string}> */
    public static function valoresValidos(): array
    {
        return [
            'voltaje entero' => [self::VOLTAJE, '24', '24V'],
            'voltaje con unidad anterior' => [self::VOLTAJE, '12v', '12V'],
            'voltaje con espacios' => [self::VOLTAJE, ' 24 V ', '24V'],
            'voltaje decimal con coma' => [self::VOLTAJE, '7,5', '7.5V'],
            'voltaje doble' => [self::VOLTAJE, '12/24', '12/24V'],
            'voltaje vacío' => [self::VOLTAJE, '', null],
            'voltaje guion' => [self::VOLTAJE, '-', null],
            'diámetro con unidad' => [self::DIAMETRO, '12mm', '12MM'],
            'diámetro con ceros' => [self::DIAMETRO, '059,50', '59.5MM'],
            'texto a mayúsculas' => [self::MARCA, 'bosch ', 'BOSCH'],
            'texto libre' => [self::NOTA, 'Sólo Ford', 'Sólo Ford'],
        ];
    }

    #[DataProvider('valoresValidos')]
    public function test_crear_y_editar_guardan_el_valor_con_su_formato(int $caracteristica, mixed $entrada, ?string $esperado): void
    {
        $this->post('/dashboard/producto/store', $this->datos([$caracteristica => $entrada]))
            ->assertRedirect()->assertSessionHasNoErrors();
        $id = (int) DB::table('productos')->value('id');
        $this->assertSame($esperado === null ? [] : [$caracteristica => $esperado], $this->guardados($id));

        // Al editar, junto con otra característica numérica.
        [$otra, $otraGuardada] = $caracteristica === self::VOLTAJE ? [self::DIAMETRO, '6MM'] : [self::VOLTAJE, '6V'];
        $this->put('/dashboard/productos/actualizar?id='.$id, $this->datos([$caracteristica => $entrada, $otra => '6']))
            ->assertRedirect()->assertSessionHasNoErrors();
        $esperados = array_filter([$caracteristica => $esperado, $otra => $otraGuardada]);
        ksort($esperados);
        $this->assertSame($esperados, $this->guardados($id));
    }

    /** @return array<string, array{int, mixed}> */
    public static function valoresInvalidos(): array
    {
        return [
            'voltaje texto' => [self::VOLTAJE, 'doce'],
            'voltaje negativo' => [self::VOLTAJE, '-12'],
            'voltaje doble unidad' => [self::VOLTAJE, '24vv'],
            'voltaje otra unidad' => [self::VOLTAJE, '24MM'],
            'diámetro con unidad de voltaje' => [self::DIAMETRO, '24v'],
            'diámetro unidad duplicada' => [self::DIAMETRO, '24MMMM'],
            'un array' => [self::VOLTAJE, ['24']],
        ];
    }

    #[DataProvider('valoresInvalidos')]
    public function test_rechaza_valores_invalidos_en_su_campo_antes_de_guardar(int $caracteristica, mixed $entrada): void
    {
        $this->postJson('/dashboard/producto/store', $this->datos([$caracteristica => $entrada, self::MARCA => 'bosch']))
            ->assertUnprocessable()->assertJsonValidationErrors('caracteristicas.'.$caracteristica);
        $this->assertSame(0, DB::table('productos')->count());

        $this->post('/dashboard/producto/store', $this->datos([self::VOLTAJE => '12']))->assertRedirect();
        $id = (int) DB::table('productos')->value('id');
        $this->putJson('/dashboard/productos/actualizar?id='.$id, $this->datos([$caracteristica => $entrada]) + ['nombre' => 'Otro nombre'])
            ->assertUnprocessable()->assertJsonValidationErrors('caracteristicas.'.$caracteristica);
        $this->assertSame([self::VOLTAJE => '12V'], $this->guardados($id));
        $this->assertSame('Alternador', DB::table('productos')->where('id', $id)->value('nombre'));
    }

    public function test_las_filas_vacias_se_quitan_y_las_que_no_vienen_tambien(): void
    {
        $this->post('/dashboard/producto/store', $this->datos([self::VOLTAJE => '12', self::MARCA => 'bosch', self::NOTA => 'x']))->assertRedirect();
        $id = (int) DB::table('productos')->value('id');

        $this->put('/dashboard/productos/actualizar?id='.$id, $this->datos([self::VOLTAJE => '24', self::MARCA => '']))->assertRedirect();

        $this->assertSame([self::VOLTAJE => '24V'], $this->guardados($id));
    }

    public function test_un_guardado_sin_la_seccion_de_caracteristicas_no_las_borra(): void
    {
        $this->post('/dashboard/producto/store', $this->datos([self::VOLTAJE => '12']))->assertRedirect();
        $id = (int) DB::table('productos')->value('id');

        $datos = $this->datos([]);
        unset($datos['caracteristicas_presente'], $datos['caracteristicas']);
        $this->put('/dashboard/productos/actualizar?id='.$id, $datos)->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame([self::VOLTAJE => '12V'], $this->guardados($id));
    }
}
