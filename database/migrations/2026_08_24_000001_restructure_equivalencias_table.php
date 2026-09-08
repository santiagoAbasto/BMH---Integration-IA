<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La tabla `equivalencias` vieja guardaba un único texto suelto
     * (`descripcion`) que ninguna búsqueda ni vista consumía.
     * Se reemplaza por una estructura clave-valor ordenada:
     * nombre (etiqueta opcional, ej: "Bosch") + valor (el código en sí).
     *
     * Las filas previas eran datos de prueba/corruptos (IDs guardados como
     * texto), así que se descartan.
     */
    public function up(): void
    {
        // Algunos despliegues legacy tenían esta tabla creada manualmente y no
        // una migración base versionada. En una instalación nueva se crea ya
        // con el esquema normalizado; en una existente se conserva el camino
        // de conversión de abajo.
        if (! Schema::hasTable('equivalencias')) {
            Schema::create('equivalencias', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('producto_id');
                $table->string('nombre')->nullable();
                $table->string('valor');
                $table->unsignedInteger('orden')->default(0);
                $table->timestamps();

                $table->index(['producto_id', 'orden']);
                $table->foreign('producto_id')
                    ->references('id')->on('productos')
                    ->onDelete('cascade');
            });

            return;
        }

        Schema::table('equivalencias', function (Blueprint $table): void {
            $table->dropColumn('descripcion');
        });

        Schema::table('equivalencias', function (Blueprint $table): void {
            $table->string('nombre')->nullable()->after('producto_id');
            $table->string('valor')->after('nombre');
            $table->unsignedInteger('orden')->default(0)->after('valor');
            $table->index(['producto_id', 'orden']);
        });

        DB::table('equivalencias')->delete();
    }

    public function down(): void
    {
        if (! Schema::hasTable('equivalencias')) {
            return;
        }

        Schema::table('equivalencias', function (Blueprint $table): void {
            $table->dropIndex(['producto_id', 'orden']);
            $table->dropColumn(['nombre', 'valor', 'orden']);
        });

        Schema::table('equivalencias', function (Blueprint $table): void {
            $table->longText('descripcion')->after('id');
        });
    }
};
