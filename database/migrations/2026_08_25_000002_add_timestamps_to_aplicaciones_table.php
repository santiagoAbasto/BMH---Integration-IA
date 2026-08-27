<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La tabla `aplicaciones` se creó sin timestamps; el repo usa inserciones
     * por query builder que envían created_at/updated_at (igual que
     * `equivalencias`). Se agregan las columnas para que el insert funcione.
     */
    public function up(): void
    {
        Schema::table('aplicaciones', function (Blueprint $table): void {
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('aplicaciones', function (Blueprint $table): void {
            $table->dropTimestamps();
        });
    }
};
