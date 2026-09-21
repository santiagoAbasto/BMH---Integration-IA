<?php

use App\Models\Apariencia;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Colores de la barra de contacto de arriba del header (admin → Extras →
     * Barra superior). Hasta ahora eran un style inline y reglas fijas en
     * styles2.css; los valores de fábrica reproducen exactamente eso.
     *
     * Sólo se agregan si faltan, igual que la franja del footer.
     */
    public function up(): void
    {
        if (! Schema::hasTable('apariencia')) {
            return;
        }

        $faltan = array_values(array_filter(
            Apariencia::CAMPOS_BARRA,
            fn (string $columna) => ! Schema::hasColumn('apariencia', $columna)
        ));

        if ($faltan === []) {
            return;
        }

        Schema::table('apariencia', function (Blueprint $table) use ($faltan): void {
            foreach ($faltan as $columna) {
                $table->string($columna, 7)->default(Apariencia::DEFAULTS_BARRA[$columna]);
            }
        });

        Apariencia::olvidarCache();
    }

    public function down(): void
    {
        // Sin doctrine/dbal, SQLite no puede borrar columnas en Laravel 10; las
        // columnas no molestan si se vuelve atrás el código.
    }
};
