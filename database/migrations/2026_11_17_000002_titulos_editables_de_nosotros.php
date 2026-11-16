<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nosotros: los dos títulos de la página pasan a editarse desde el admin (antes estaban fijos en la vista). Vacíos,
 * la página usa los del Figma. Aditiva.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nosotros_contenido', function (Blueprint $table): void {
            if (! Schema::hasColumn('nosotros_contenido', 'historia_titulo')) {
                $table->string('historia_titulo', 160)->nullable()->after('id');
            }
            if (! Schema::hasColumn('nosotros_contenido', 'valores_titulo')) {
                $table->string('valores_titulo', 120)->nullable()->after('valores');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nosotros_contenido', function (Blueprint $table): void {
            $table->dropColumn(['historia_titulo', 'valores_titulo']);
        });
    }
};
