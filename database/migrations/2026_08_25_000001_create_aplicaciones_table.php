<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla `aplicaciones`: igual estructura que `equivalencias` pero para
     * modelos de otros fabricantes en los que aplica el producto.
     * nombre (etiqueta opcional, ej: "Bosch") + valor (el modelo en sí).
     */
    public function up(): void
    {
        Schema::create('aplicaciones', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('producto_id');
            $table->string('nombre')->nullable();
            $table->string('valor');
            $table->unsignedInteger('orden')->default(0);
            $table->index(['producto_id', 'orden']);
            $table->foreign('producto_id')
                ->references('id')->on('productos')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aplicaciones');
    }
};
