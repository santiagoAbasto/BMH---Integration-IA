<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `categorias.nombre` estaba en utf8mb3_bin (comparación binaria): buscar «alternadores» no encontraba
 * «ALTERNADORES» (admin → «Traer las de otra categoría», importación de Excel por nombre de categoría). Pasa a
 * utf8mb4_unicode_ci como el resto de la tabla y de la base. No cambia los datos (no hay nombres que difieran sólo
 * en mayúsculas ni índices sobre la columna).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('categorias', 'nombre')) {
            DB::statement('ALTER TABLE categorias MODIFY nombre VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('categorias', 'nombre')) {
            DB::statement('ALTER TABLE categorias MODIFY nombre VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_bin NULL');
        }
    }
};
