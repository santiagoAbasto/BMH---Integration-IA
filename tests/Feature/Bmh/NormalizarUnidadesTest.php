<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Services\Catalogo\NormalizadorUnidades;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * php artisan catalogo:normalizar-unidades — voltaje y diámetro ya cargados
 * pasan al formato de la carga nueva (12v, 60MM). SQLite en memoria.
 */
final class NormalizarUnidadesTest extends TestCase
{
    /** @var list<string> */
    private array $csvPrevios = [];

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
            $t->string('nombre')->nullable();
            $t->string('columna_1')->nullable();
            $t->string('columna_2')->nullable();
            $t->string('columna_3')->nullable();
        });
        Schema::create('productos', function (Blueprint $t): void {
            $t->id();
            $t->string('codigo')->nullable();
            $t->string('nombre')->nullable();
            $t->integer('categoria_id')->nullable();
            $t->string('columna_1')->nullable();
            $t->string('columna_2')->nullable();
            $t->string('columna_3')->nullable();
        });
        Schema::create('caracteristicas', function (Blueprint $t): void {
            $t->id();
            $t->string('nombre');
        });
        Schema::create('producto_caracteristica', function (Blueprint $t): void {
            $t->integer('producto_id');
            $t->integer('caracteristica_id');
            $t->string('valor')->nullable();
        });

        DB::table('categorias')->insert(['id' => 1, 'nombre' => 'Arranques', 'columna_1' => 'VOLTAJE', 'columna_2' => 'Diámetro exterior', 'columna_3' => 'Dientes']);
        $productos = [
            [1, 'A1', '12', '60', '12'],
            [2, 'A2', '12 V', '60mm', '9'],
            [3, 'A3', '24V', '7,5', '10'],
            [4, 'A4', '12v', '60MM', '11'],
            [5, 'A5', '12/24V', '128x138', '12'],
            [6, 'A6', '-', '.', '13'],
        ];
        foreach ($productos as [$id, $codigo, $voltaje, $diametro, $dientes]) {
            DB::table('productos')->insert(['id' => $id, 'codigo' => $codigo, 'nombre' => 'Producto '.$codigo, 'categoria_id' => 1,
                'columna_1' => $voltaje, 'columna_2' => $diametro, 'columna_3' => $dientes]);
        }
        DB::table('caracteristicas')->insert([['id' => 1, 'nombre' => 'Voltaje'], ['id' => 2, 'nombre' => 'Diametro interior'], ['id' => 3, 'nombre' => 'Largo']]);
        DB::table('producto_caracteristica')->insert([
            ['producto_id' => 1, 'caracteristica_id' => 1, 'valor' => '24'],
            ['producto_id' => 1, 'caracteristica_id' => 2, 'valor' => '17 mm'],
            ['producto_id' => 1, 'caracteristica_id' => 3, 'valor' => '120'],
        ]);

        $this->csvPrevios = glob(storage_path('app/unidades-a-revisar-*.csv')) ?: [];
    }

    protected function tearDown(): void
    {
        foreach (array_diff(glob(storage_path('app/unidades-a-revisar-*.csv')) ?: [], $this->csvPrevios) as $nuevo) {
            File::delete($nuevo);
        }
        parent::tearDown();
    }

    private function columna(string $campo): array
    {
        return DB::table('productos')->orderBy('id')->pluck($campo, 'codigo')->all();
    }

    public function test_el_formato_es_el_de_la_carga_nueva(): void
    {
        $this->assertSame('12v', NormalizadorUnidades::canonico('12', 'v'));
        $this->assertSame('12v', NormalizadorUnidades::canonico(' 12 V ', 'v'));
        $this->assertSame('12.5v', NormalizadorUnidades::canonico('12,5V', 'v'));
        $this->assertSame('60MM', NormalizadorUnidades::canonico('60 mm', 'MM'));
        // Un mismo número, una sola forma: sin ceros de más.
        $this->assertSame('89.4MM', NormalizadorUnidades::canonico('89.40', 'MM'));
        $this->assertSame('89.5MM', NormalizadorUnidades::canonico('89,50 MM', 'MM'));
        $this->assertSame('12v', NormalizadorUnidades::canonico('12.0V', 'v'));
        $this->assertSame('12v', NormalizadorUnidades::canonico('012', 'v'));
        $this->assertSame('0v', NormalizadorUnidades::canonico('0', 'v'));
        $this->assertSame('0.5MM', NormalizadorUnidades::canonico('0.50', 'MM'));
        $this->assertNull(NormalizadorUnidades::canonico('12/24V', 'v'));
        $this->assertNull(NormalizadorUnidades::canonico('60mm', 'v'), 'otra unidad no se convierte');
    }

    public function test_unifica_numeros_y_deja_igual_lo_demas(): void
    {
        $this->artisan('catalogo:normalizar-unidades')
            ->expectsOutputToContain('6 valores en columnas y 2 en características unificados · 2 ya estaban bien')
            ->expectsOutputToContain('2 valores no son un número')
            ->assertSuccessful();

        $this->assertSame(['A1' => '12v', 'A2' => '12v', 'A3' => '24v', 'A4' => '12v', 'A5' => '12/24V', 'A6' => '-'], $this->columna('columna_1'));
        $this->assertSame(['A1' => '60MM', 'A2' => '60MM', 'A3' => '7.5MM', 'A4' => '60MM', 'A5' => '128x138', 'A6' => '.'], $this->columna('columna_2'));
        // Una columna sin unidad no se toca.
        $this->assertSame(['A1' => '12', 'A2' => '9', 'A3' => '10', 'A4' => '11', 'A5' => '12', 'A6' => '13'], $this->columna('columna_3'));

        $caracteristicas = DB::table('producto_caracteristica')->orderBy('caracteristica_id')->pluck('valor')->all();
        $this->assertSame(['24v', '17MM', '120'], $caracteristicas);

        // Lo que no se pudo queda en un CSV para revisar a mano.
        $csv = array_values(array_diff(glob(storage_path('app/unidades-a-revisar-*.csv')) ?: [], $this->csvPrevios));
        $this->assertCount(1, $csv);
        $contenido = (string) file_get_contents($csv[0]);
        $this->assertStringContainsString('A5;"Producto A5";VOLTAJE;12/24V', $contenido);
        $this->assertStringContainsString('128x138', $contenido);

        // Correrlo de nuevo no cambia nada.
        $this->artisan('catalogo:normalizar-unidades')->expectsOutputToContain('0 valores en columnas y 0 en características unificados')->assertSuccessful();
    }

    public function test_el_filtro_ordena_las_medidas_por_su_valor(): void
    {
        $comparar = new \ReflectionMethod(\App\Services\Catalogo\CatalogoFiltrado::class, 'compararMedidas');
        $comparar->setAccessible(true);
        $valores = ['89.5MM', '4MM', '12/24V', '125MM', '89MM', '4.4MM', '12V', '89.4MM', '-'];
        usort($valores, fn ($a, $b) => $comparar->invoke(null, $a, $b));

        $this->assertSame(['4MM', '4.4MM', '12V', '12/24V', '89MM', '89.4MM', '89.5MM', '125MM', '-'], $valores);
    }

    public function test_en_modo_prueba_no_cambia_nada(): void
    {
        $this->artisan('catalogo:normalizar-unidades', ['--dry-run' => true])
            ->expectsOutputToContain('[prueba, no se cambió nada] 6 valores en columnas y 2 en características a unificar')
            ->assertSuccessful();

        $this->assertSame('12', DB::table('productos')->where('id', 1)->value('columna_1'));
        $this->assertSame('24', DB::table('producto_caracteristica')->where('caracteristica_id', 1)->value('valor'));
    }
}
