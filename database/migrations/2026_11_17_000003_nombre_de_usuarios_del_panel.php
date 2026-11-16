<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Usuarios del panel: el formulario de alta mandaba siempre name=«alberto» en un campo oculto, así que todos los
 * creados desde el admin se llamaban así (y el panel lo muestra abajo a la izquierda). Se les pone su usuario.
 * Sólo toca los que dicen exactamente «alberto»; no cambia contraseñas ni nada más.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admins')) {
            DB::table('admins')->where('name', 'alberto')->whereNotNull('username')->update(['name' => DB::raw('username')]);
        }
    }

    public function down(): void
    {
        // Sin vuelta atrás: «alberto» no era un dato real.
    }
};
