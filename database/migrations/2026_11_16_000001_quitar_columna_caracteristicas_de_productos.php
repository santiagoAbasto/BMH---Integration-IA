<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `productos.caracteristicas` era un campo de texto viejo (sin uso: vacío en todos los productos). Con el mismo
 * nombre que la relación Producto::caracteristicas(), Eloquent devolvía la columna en lugar de la relación y
 * `$producto->caracteristicas` daba null (el admin no mostraba las características cargadas).
 *
 * Sin perder datos: si está vacía se quita; si algún producto tuviera texto, se renombra a `caracteristicas_texto`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('productos', 'caracteristicas')) {
            return;
        }

        $conTexto = DB::table('productos')->whereNotNull('caracteristicas')->whereRaw("TRIM(caracteristicas) <> ''")->exists();

        Schema::table('productos', function (Blueprint $table) use ($conTexto): void {
            $conTexto
                ? $table->renameColumn('caracteristicas', 'caracteristicas_texto')
                : $table->dropColumn('caracteristicas');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('productos', 'caracteristicas_texto')) {
            Schema::table('productos', fn (Blueprint $table) => $table->renameColumn('caracteristicas_texto', 'caracteristicas'));
        } elseif (! Schema::hasColumn('productos', 'caracteristicas')) {
            Schema::table('productos', fn (Blueprint $table) => $table->text('caracteristicas')->nullable());
        }
    }
};
