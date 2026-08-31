<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega a `productos` el criterio de ordenamiento por sección:
     * - 'manual'     → usa el valor del campo "orden" (código alfanumérico).
     * - 'alfa_asc'   → alfabético ascendente por nombre.
     * - 'alfa_desc'  → alfabético descendente por nombre.
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            $table->string('orden_equivalencias', 12)->default('manual')->after('orden');
            $table->string('orden_aplicaciones', 12)->default('manual')->after('orden_equivalencias');
            $table->string('orden_partes', 12)->default('manual')->after('orden_aplicaciones');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            $table->dropColumn(['orden_equivalencias', 'orden_aplicaciones', 'orden_partes']);
        });
    }
};
