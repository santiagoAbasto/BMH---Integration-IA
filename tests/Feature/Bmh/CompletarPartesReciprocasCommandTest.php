<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** php artisan partes:completar-reciprocas — SQLite en memoria. */
final class CompletarPartesReciprocasCommandTest extends TestCase
{
    /** @var list<string> */
    private array $registrosPrevios = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite_memoria',
            'database.connections.sqlite_memoria' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);
        DB::purge('sqlite_memoria');

        Schema::create('productos', function (Blueprint $t): void {
            $t->id();
            $t->string('codigo');
        });
        Schema::create('partes_relacionadas', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('producto_id');
            $t->unsignedBigInteger('parte_id');
            $t->string('orden', 2)->default('aa');
            $t->timestamps();
            $t->unique(['producto_id', 'parte_id']);
        });

        DB::table('productos')->insert([['id' => 1, 'codigo' => 'NFSO42'], ['id' => 2, 'codigo' => '0078'], ['id' => 3, 'codigo' => '0079']]);
        DB::table('partes_relacionadas')->insert([
            ['producto_id' => 1, 'parte_id' => 2, 'orden' => 'aa'], // de un solo lado
            ['producto_id' => 1, 'parte_id' => 3, 'orden' => 'ab'], // ya recíproca
            ['producto_id' => 3, 'parte_id' => 1, 'orden' => 'aa'],
        ]);

        $this->registrosPrevios = glob(storage_path('app/partes-reciprocas-*.json')) ?: [];
    }

    protected function tearDown(): void
    {
        foreach (array_diff(glob(storage_path('app/partes-reciprocas-*.json')) ?: [], $this->registrosPrevios) as $nuevo) {
            File::delete($nuevo);
        }
        parent::tearDown();
    }

    private function pares(): array
    {
        return DB::table('partes_relacionadas')->orderBy('producto_id')->orderBy('parte_id')
            ->get(['producto_id', 'parte_id'])->map(fn ($f) => [(int) $f->producto_id, (int) $f->parte_id])->all();
    }

    public function test_completa_las_de_un_solo_lado_y_no_toca_las_demas(): void
    {
        $this->artisan('partes:completar-reciprocas')
            ->expectsOutputToContain('1 relaciones agregadas')
            ->assertSuccessful();

        $this->assertSame([[1, 2], [1, 3], [2, 1], [3, 1]], $this->pares());

        // Correrlo de nuevo no cambia nada.
        $this->artisan('partes:completar-reciprocas')->expectsOutputToContain('de un solo lado: 0')->assertSuccessful();
        $this->assertCount(4, $this->pares());
    }

    public function test_en_modo_prueba_no_guarda_nada(): void
    {
        $this->artisan('partes:completar-reciprocas', ['--dry-run' => true])
            ->expectsOutputToContain('[prueba, deshecho] 1 relaciones agregadas')
            ->assertSuccessful();

        $this->assertSame([[1, 2], [1, 3], [3, 1]], $this->pares());
    }
}
