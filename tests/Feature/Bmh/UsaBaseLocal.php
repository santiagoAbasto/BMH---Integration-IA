<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use Illuminate\Support\Facades\DB;

/**
 * Para los tests que escriben en la base local `bmh_legacy` (como OfertasTest): cambia la conexión por
 * defecto al usuario con escritura y la deja como estaba al terminar. Cada test crea y borra sus datos.
 */
trait UsaBaseLocal
{
    private string $baseOriginal = '';

    /** @var array<string, mixed> */
    private array $mysqlOriginal = [];

    private function usarBaseLocal(): void
    {
        $this->baseOriginal = (string) config('database.default');
        $this->mysqlOriginal = (array) config('database.connections.mysql');
        config([
            'database.connections.mysql.database' => 'bmh_legacy',
            'database.connections.mysql.username' => 'bmh_app',
            'database.connections.mysql.password' => 'bmh_app_local',
            'database.default' => 'mysql',
        ]);
        DB::purge('mysql');
    }

    /** @var list<int> características que creó el test (se borran al terminar) */
    private array $caracteristicasCreadas = [];

    /**
     * La característica con ese nombre: la que ya existe en la base o una nueva (que se borra al final).
     *
     * @param  array<string, mixed>  $formato  mayusculas, numerica, unidad, orden
     */
    private function caracteristica(string $nombre, array $formato = []): int
    {
        $existente = DB::table('caracteristicas')->where('nombre', $nombre)->value('id');
        if ($existente !== null) {
            return (int) $existente;
        }

        return $this->caracteristicasCreadas[] = (int) DB::table('caracteristicas')->insertGetId(
            ['nombre' => $nombre, 'created_at' => now(), 'updated_at' => now()] + $formato
        );
    }

    /** La categoría declara esas características (se borran con la categoría, por la clave foránea). */
    private function vincularCaracteristicas(int $categoriaId, int ...$caracteristicaIds): void
    {
        foreach ($caracteristicaIds as $id) {
            DB::table('caracteristica_categoria')->insertOrIgnore(['categoria_id' => $categoriaId, 'caracteristica_id' => $id]);
        }
    }

    /** El valor tal cual (sin normalizar), como podía estar cargado. Se borra con el producto. */
    private function cargarValor(int $productoId, int $caracteristicaId, string $valor): void
    {
        DB::table('caracteristica_producto')->updateOrInsert(
            ['producto_id' => $productoId, 'caracteristica_id' => $caracteristicaId],
            ['valor' => $valor]
        );
    }

    private function restaurarBase(): void
    {
        if ($this->caracteristicasCreadas !== []) {
            DB::table('caracteristicas')->whereIn('id', $this->caracteristicasCreadas)->delete();
            $this->caracteristicasCreadas = [];
        }
        config(['database.default' => $this->baseOriginal, 'database.connections.mysql' => $this->mysqlOriginal]);
        DB::purge('mysql');
    }
}
