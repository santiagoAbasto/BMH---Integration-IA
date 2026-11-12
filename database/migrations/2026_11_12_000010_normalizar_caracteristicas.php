<?php

declare(strict_types=1);

use App\Support\Caracteristicas\MigracionCaracteristicas;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Características relacionales (pedido del usuario, 2026-11-12):
 *  - `caracteristicas`: id, nombre (único), orden (2 caracteres alfanuméricos) y un formato opcional
 *    (mayúsculas, numérica con unidad, p. ej. VOLTAJE → «12V»). Cada `columna_N` vieja pasa a la
 *    característica del mismo nombre (EQUIVALENCIA estaba en dos columnas: queda una sola).
 *  - `caracteristica_categoria` y `caracteristica_producto` (con el valor): tablas intermedias con
 *    claves foráneas y clave primaria por par.
 * Después carga los datos (MigracionCaracteristicas). No borra nada: las columnas `columna_N` y las tablas
 * `categoria_caracteristica` / `producto_caracteristica` quedan como respaldo.
 */
return new class extends Migration
{
    public function up(): void
    {
        // La tabla estaba en armscii8 (orden) y utf8mb3 (nombre): pasa a utf8mb4 como el resto. Los nombres ya
        // estaban en UTF-8 válido y `orden` vacío, así que la conversión no cambia datos.
        DB::statement('ALTER TABLE caracteristicas CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        Schema::table('caracteristicas', function (Blueprint $table): void {
            if (! Schema::hasColumn('caracteristicas', 'mayusculas')) {
                $table->boolean('mayusculas')->default(true)->after('nombre');
            }
            if (! Schema::hasColumn('caracteristicas', 'numerica')) {
                $table->boolean('numerica')->default(false)->after('mayusculas');
            }
            if (! Schema::hasColumn('caracteristicas', 'unidad')) {
                $table->string('unidad', 10)->nullable()->after('numerica');
            }
        });
        Schema::table('caracteristicas', function (Blueprint $table): void {
            $table->string('orden', 2)->nullable()->change();
            if (! Schema::hasIndex('caracteristicas', 'caracteristicas_nombre_unique')) {
                $table->unique('nombre');
            }
            if (! Schema::hasIndex('caracteristicas', 'caracteristicas_orden_index')) {
                $table->index('orden');
            }
        });

        if (! Schema::hasTable('caracteristica_categoria')) {
            Schema::create('caracteristica_categoria', function (Blueprint $table): void {
                $table->integer('caracteristica_id');
                $table->unsignedBigInteger('categoria_id');
                $table->timestamps();
                $table->primary(['categoria_id', 'caracteristica_id']);
                $table->index('caracteristica_id');
                $table->foreign('caracteristica_id')->references('id')->on('caracteristicas')->cascadeOnDelete();
                $table->foreign('categoria_id')->references('id')->on('categorias')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('caracteristica_producto')) {
            Schema::create('caracteristica_producto', function (Blueprint $table): void {
                $table->integer('caracteristica_id');
                $table->unsignedBigInteger('producto_id');
                $table->string('valor', 255);
                $table->timestamps();
                $table->primary(['producto_id', 'caracteristica_id']);
                // Filtros del catálogo: «productos con VOLTAJE = 12V».
                $table->index(['caracteristica_id', 'valor']);
                $table->foreign('caracteristica_id')->references('id')->on('caracteristicas')->cascadeOnDelete();
                $table->foreign('producto_id')->references('id')->on('productos')->cascadeOnDelete();
            });
        }

        (new MigracionCaracteristicas())->ejecutar(true);
    }

    public function down(): void
    {
        Schema::dropIfExists('caracteristica_producto');
        Schema::dropIfExists('caracteristica_categoria');

        // Las características creadas desde las columnas viejas (sin uso en las tablas anteriores) se quitan.
        DB::table('caracteristicas')
            ->whereNotIn('id', DB::table('categoria_caracteristica')->select('caracteristica_id'))
            ->whereNotIn('id', DB::table('producto_caracteristica')->select('caracteristica_id'))
            ->delete();

        Schema::table('caracteristicas', function (Blueprint $table): void {
            $table->dropUnique(['nombre']);
            $table->dropIndex(['orden']);
            $table->dropColumn(['mayusculas', 'numerica', 'unidad']);
        });
        DB::table('caracteristicas')->update(['orden' => null]);
    }
};
