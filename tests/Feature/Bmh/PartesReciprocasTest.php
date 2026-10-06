<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Services\Catalogo\PartesReciprocas;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Partes relacionadas en los dos sentidos: si NFSO42 tiene a 0078, 0078
 * tiene a NFSO42. Hermético: SQLite en memoria, con la misma clave única
 * que la tabla real.
 */
final class PartesReciprocasTest extends TestCase
{
    private const NFSO42 = 1;
    private const P0078 = 2;
    private const P0079 = 3;
    private const OTRA = 9;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite_memoria',
            'database.connections.sqlite_memoria' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);
        DB::purge('sqlite_memoria');

        Schema::create('partes_relacionadas', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('producto_id');
            $t->unsignedBigInteger('parte_id');
            $t->string('orden', 2)->default('aa');
            $t->timestamps();
            $t->unique(['producto_id', 'parte_id']);
        });
    }

    /** @return array<int, list<array{int, string}>> producto => [[parte, orden], …] */
    private function listas(): array
    {
        $listas = [];
        foreach (DB::table('partes_relacionadas')->orderBy('producto_id')->orderBy('orden')->get() as $fila) {
            $listas[(int) $fila->producto_id][] = [(int) $fila->parte_id, $fila->orden];
        }

        return $listas;
    }

    private function relacionar(int $producto, int $parte, string $orden): void
    {
        DB::table('partes_relacionadas')->insert(['producto_id' => $producto, 'parte_id' => $parte, 'orden' => $orden]);
    }

    public function test_la_parte_agregada_tiene_al_producto_al_final_de_su_lista(): void
    {
        // El admin guardó NFSO42 con 0078 y 0079; 0078 ya tenía otra parte.
        $this->relacionar(self::NFSO42, self::P0078, 'aa');
        $this->relacionar(self::NFSO42, self::P0079, 'ab');
        $this->relacionar(self::P0078, self::OTRA, 'aa');

        (new PartesReciprocas())->sincronizar(self::NFSO42, [self::P0078, self::P0079], []);

        $listas = $this->listas();
        $this->assertSame([[self::OTRA, 'aa'], [self::NFSO42, 'ab']], $listas[self::P0078]);
        $this->assertSame([[self::NFSO42, 'aa']], $listas[self::P0079]);
    }

    public function test_si_ya_lo_tenia_no_lo_duplica_ni_le_cambia_el_orden(): void
    {
        $this->relacionar(self::NFSO42, self::P0078, 'aa');
        $this->relacionar(self::P0078, self::OTRA, 'aa');
        $this->relacionar(self::P0078, self::NFSO42, 'zz');

        $servicio = new PartesReciprocas();
        $servicio->sincronizar(self::NFSO42, [self::P0078], []);
        $servicio->sincronizar(self::NFSO42, [self::P0078], []);

        $this->assertSame([[self::OTRA, 'aa'], [self::NFSO42, 'zz']], $this->listas()[self::P0078]);
    }

    public function test_la_parte_que_se_saca_tambien_lo_saca_de_su_lista(): void
    {
        $this->relacionar(self::P0078, self::NFSO42, 'aa');
        $this->relacionar(self::P0078, self::OTRA, 'ab');
        $this->relacionar(self::P0079, self::NFSO42, 'aa');

        // Se sacó 0078 de NFSO42; 0079 sigue.
        (new PartesReciprocas())->sincronizar(self::NFSO42, [self::P0079], [self::P0078]);

        $listas = $this->listas();
        $this->assertSame([[self::OTRA, 'ab']], $listas[self::P0078], 'sólo se va NFSO42, las demás quedan');
        $this->assertSame([[self::NFSO42, 'aa']], $listas[self::P0079]);
    }

    public function test_nunca_relaciona_un_producto_consigo_mismo(): void
    {
        (new PartesReciprocas())->sincronizar(self::NFSO42, [self::NFSO42, self::P0078], [self::NFSO42]);

        $this->assertSame([self::P0078 => [[self::NFSO42, 'aa']]], $this->listas());
    }

    public function test_sin_partes_ni_quitadas_no_toca_nada(): void
    {
        $this->relacionar(self::P0078, self::OTRA, 'aa');

        (new PartesReciprocas())->sincronizar(self::NFSO42, [], []);

        $this->assertSame([self::P0078 => [[self::OTRA, 'aa']]], $this->listas());
    }
}
