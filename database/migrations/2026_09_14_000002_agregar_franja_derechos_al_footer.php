<?php

use App\Models\Apariencia;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Colores de la franja del copyright, al pie del footer. Hasta ahora eran
     * estilos inline fijos en plantilla-front.
     *
     * En una instalación nueva la migración de creación ya arma estas columnas
     * (lee Apariencia::CAMPOS_FOOTER), así que acá sólo se agregan si faltan.
     */
    private const COLUMNAS = ['footer_derechos_fondo', 'footer_derechos_texto'];

    public function up(): void
    {
        if (! Schema::hasTable('apariencia')) {
            return;
        }

        $faltan = array_values(array_filter(self::COLUMNAS, fn (string $c) => ! Schema::hasColumn('apariencia', $c)));
        if ($faltan === []) {
            return;
        }

        Schema::table('apariencia', function (Blueprint $table) use ($faltan): void {
            foreach ($faltan as $columna) {
                $table->string($columna, 7)->default(Apariencia::DEFAULTS_FOOTER[$columna]);
            }
        });

        Apariencia::olvidarCache();
    }

    public function down(): void
    {
        // Sin doctrine/dbal, SQLite no puede borrar columnas en Laravel 10; y
        // la migración de creación ya las incluye, así que se dejan.
    }
};
