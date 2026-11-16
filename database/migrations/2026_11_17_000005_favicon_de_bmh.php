<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño: el favicon pasa a ser el de BMH (las tres barras del logo, public/favicon.svg / .ico). El que estaba
 * cargado en Extras › Favicon era el logo entero achicado a un cuadrado (media_6aa8425186d99.png), ilegible a 16px:
 * se quita sólo esa fila para que se vea el nuevo. El archivo no se borra (puede estar en uso en producción) y en
 * Extras › Favicon se puede volver a subir uno propio.
 */
return new class extends Migration
{
    private const FAVICON_VIEJO = 'media_6aa8425186d99.png';

    public function up(): void
    {
        if (Schema::hasTable('imagenes')) {
            DB::table('imagenes')->where('sector', 'favicon')->where('path', self::FAVICON_VIEJO)->delete();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('imagenes') && ! DB::table('imagenes')->where('sector', 'favicon')->exists()) {
            DB::table('imagenes')->insert(['sector' => 'favicon', 'path' => self::FAVICON_VIEJO, 'orden' => 'aa', 'tipo' => 'imagen', 'created_at' => now(), 'updated_at' => now()]);
        }
    }
};
