<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Convierte `orden` de UNSIGNED INT a VARCHAR(2) en las tres tablas para
     * permitir códigos alfanuméricos de hasta 2 caracteres (ej: aa, a1, b2).
     * Se recorta primero cualquier valor numérico previo > 2 dígitos para
     * evitar truncado al alterar la columna.
     */
    public function up(): void
    {
        $tablas = ['equivalencias', 'aplicaciones', 'partes_relacionadas'];

        foreach ($tablas as $tabla) {
            DB::statement("UPDATE `{$tabla}` SET `orden` = LEFT(CAST(`orden` AS CHAR), 2)");
            DB::statement("ALTER TABLE `{$tabla}` MODIFY `orden` VARCHAR(2) NOT NULL DEFAULT '0'");
        }
    }

    public function down(): void
    {
        $tablas = ['equivalencias', 'aplicaciones', 'partes_relacionadas'];

        foreach ($tablas as $tabla) {
            DB::statement("ALTER TABLE `{$tabla}` MODIFY `orden` UNSIGNED INT NOT NULL DEFAULT 0");
        }
    }
};
