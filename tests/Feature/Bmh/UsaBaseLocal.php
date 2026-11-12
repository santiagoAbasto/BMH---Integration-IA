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

    private function restaurarBase(): void
    {
        config(['database.default' => $this->baseOriginal, 'database.connections.mysql' => $this->mysqlOriginal]);
        DB::purge('mysql');
    }
}
