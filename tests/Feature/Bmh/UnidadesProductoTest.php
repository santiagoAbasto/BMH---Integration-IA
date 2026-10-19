<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Admin;
use App\Services\Catalogo\UnidadesProducto;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class UnidadesProductoTest extends TestCase
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
            $t->string('columna_1');
            $t->string('columna_2');
        });
        Schema::create('caracteristicas', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
        });
        Schema::create('productos', function (Blueprint $t) {
            $t->id();
            $t->integer('categoria_id')->nullable();
            foreach ([
                'codigo', 'nombre', 'orden', 'descripcion', 'precio', 'precioN', 'descuento',
                'diametroInterno', 'diametroExterno', 'anchoBanda', 'tolerancia', 'blindaje',
                'marca', 'modelo', 'estado', 'columna_1', 'columna_2',
                'orden_equivalencias', 'orden_aplicaciones', 'orden_partes',
            ] as $campo) {
                $t->string($campo)->nullable();
            }
            $t->timestamps();
        });
        Schema::create('producto_caracteristica', function (Blueprint $t) {
            $t->id();
            $t->integer('producto_id');
            $t->integer('caracteristica_id');
            $t->string('valor')->nullable();
            $t->timestamps();
        });

        DB::table('categorias')->insert([
            ['id' => 1, 'nombre' => 'Alternadores', 'columna_1' => 'VOLTAJE', 'columna_2' => 'LARGO'],
            ['id' => 2, 'nombre' => 'Otra familia', 'columna_1' => 'MODELO', 'columna_2' => 'VOLTAJE'],
        ]);
        DB::table('caracteristicas')->insert([
            ['id' => 1, 'nombre' => 'Voltaje'],
            ['id' => 2, 'nombre' => 'MARCA'],
        ]);

        $admin = new Admin(['name' => 'Tester', 'rol' => 'administrador']);
        $admin->id = 1;
        $this->actingAs($admin, 'admin');
    }

    private function datos(mixed $valor): array
    {
        return [
            'nombre' => 'Alternador',
            'codigo' => 'ALT-1',
            'orden' => 'aa',
            'categoria' => 1,
            'columna_1' => $valor,
            'columna_2' => '100',
            'caracteristicas' => [1 => $valor, 2 => 'Marca 24'],
        ];
    }

    public static function valoresValidos(): array
    {
        $voltajes = [
            'entero' => ['24', '24v'],
            'con unidad anterior' => ['12v', '12v'],
            'unidad mayúscula y espacios' => [' 24 V ', '24v'],
            'decimal' => ['7.5', '7.5v'],
            'decimal con coma' => ['7,5', '7.5v'],
            'cero' => ['0', '0v'],
            'vacío' => ['', null],
            'sin valor' => [null, null],
        ];
        $diametros = [
            'entero' => ['24', '24MM'],
            'con unidad' => ['12MM', '12MM'],
            'unidad minúscula y espacios' => [' 24 mm ', '24MM'],
            'unidad mixta' => ['24mM', '24MM'],
            'decimal' => ['7.5', '7.5MM'],
            'decimal con coma' => ['7,5', '7.5MM'],
            'cero' => ['0', '0MM'],
            'vacío' => ['', null],
            'sin valor' => [null, null],
        ];

        $casos = [];
        foreach ($voltajes as $caso => [$entrada, $esperado]) {
            $casos['voltaje '.$caso] = ['VOLTAJE', $entrada, $esperado];
        }
        foreach (['DIAMETRO', 'DIÁMETRO', 'Diámetro interno', 'Diametro externo'] as $nombre) {
            foreach ($diametros as $caso => [$entrada, $esperado]) {
                $casos[$nombre.' '.$caso] = [$nombre, $entrada, $esperado];
            }
        }

        return $casos;
    }

    private function nombreDelCampo(string $nombre): void
    {
        DB::table('categorias')->where('id', 1)->update(['columna_1' => $nombre]);
        DB::table('caracteristicas')->where('id', 1)->update(['nombre' => $nombre]);
    }

    /** @dataProvider valoresValidos */
    public function test_crear_y_editar_guardan_el_numero_con_una_sola_unidad(string $nombre, mixed $entrada, ?string $esperado): void
    {
        $this->nombreDelCampo($nombre);
        $this->post('/dashboard/producto/store', $this->datos($entrada))->assertRedirect()->assertSessionHasNoErrors();
        $id = (int) DB::table('productos')->value('id');
        $this->comprobarValores($id, $esperado);

        $this->put('/dashboard/productos/actualizar?id='.$id, $this->datos('48'))->assertRedirect()->assertSessionHasNoErrors();
        $this->comprobarValores($id, '48'.UnidadesProducto::unidad($nombre));

        $this->put('/dashboard/productos/actualizar?id='.$id, $this->datos($entrada))->assertRedirect()->assertSessionHasNoErrors();
        $this->comprobarValores($id, $esperado);
    }

    private function comprobarValores(int $id, ?string $esperado): void
    {
        $this->assertSame($esperado, DB::table('productos')->where('id', $id)->value('columna_1'));
        $this->assertSame($esperado, DB::table('producto_caracteristica')->where('producto_id', $id)->where('caracteristica_id', 1)->value('valor'));
        // Los demás campos conservan exactamente lo que escribió el usuario.
        $this->assertSame('100', DB::table('productos')->where('id', $id)->value('columna_2'));
        $this->assertSame('Marca 24', DB::table('producto_caracteristica')->where('producto_id', $id)->where('caracteristica_id', 2)->value('valor'));
    }

    public static function valoresInvalidos(): array
    {
        $invalidos = [
            'texto' => ['doce'],
            'negativo' => ['-12'],
            'doble unidad' => ['24vv'],
            'otra unidad' => ['24w'],
            'dos números' => ['12/24'],
            'array' => [['24']],
        ];
        $casos = [];
        foreach (['VOLTAJE', 'DIÁMETRO'] as $nombre) {
            foreach ($invalidos as $caso => [$entrada]) {
                $casos[$nombre.' '.$caso] = [$nombre, $entrada];
            }
        }
        $casos['diámetro con unidad duplicada'] = ['DIÁMETRO', '24MMMM'];
        $casos['diámetro con unidad de voltaje'] = ['DIÁMETRO', '24v'];
        $casos['voltaje con unidad de diámetro'] = ['VOLTAJE', '24MM'];

        return $casos;
    }

    /** @dataProvider valoresInvalidos */
    public function test_rechaza_valores_invalidos_antes_de_crear_o_modificar(string $nombre, mixed $entrada): void
    {
        $this->nombreDelCampo($nombre);
        $this->postJson('/dashboard/producto/store', $this->datos($entrada))
            ->assertUnprocessable()->assertJsonValidationErrors('columna_1');
        $this->assertSame(0, DB::table('productos')->count());

        $this->post('/dashboard/producto/store', $this->datos('12'))->assertRedirect();
        $id = (int) DB::table('productos')->value('id');
        $datos = $this->datos('24');
        $datos['caracteristicas'][1] = $entrada;
        $this->putJson('/dashboard/productos/actualizar?id='.$id, $datos)
            ->assertUnprocessable()->assertJsonValidationErrors('caracteristicas.1');
        $this->comprobarValores($id, '12'.UnidadesProducto::unidad($nombre));
    }

    public function test_al_cambiar_de_categoria_usa_el_nombre_de_campo_de_la_nueva_familia(): void
    {
        $datos = $this->datos('Modelo A');
        $datos['categoria'] = 2;
        $datos['columna_2'] = '24';
        $datos['caracteristicas'] = [];

        $this->post('/dashboard/producto/store', $datos)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Modelo A', DB::table('productos')->value('columna_1'));
        $this->assertSame('24v', DB::table('productos')->value('columna_2'));
    }

    public function test_detecta_las_unidades_sin_confundir_otras_caracteristicas(): void
    {
        $this->assertSame('v', UnidadesProducto::unidad('VOLTAJE'));
        $this->assertSame('v', UnidadesProducto::unidad('Voltaje de salida'));
        $this->assertSame('MM', UnidadesProducto::unidad('DIAMETRO'));
        $this->assertSame('MM', UnidadesProducto::unidad('DIÁMETRO'));
        $this->assertSame('MM', UnidadesProducto::unidad('Diámetro interno'));
        $this->assertSame('MM', UnidadesProducto::unidad('Diametro externo'));
        $this->assertNull(UnidadesProducto::unidad('MODELO'));
        $this->assertNull(UnidadesProducto::unidad(null));
    }
}
