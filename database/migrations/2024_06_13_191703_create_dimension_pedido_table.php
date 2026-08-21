<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dimension_pedido', function (Blueprint $table) {
            $table->id();
            $table->string('cantidad');
            $table->string('precio_unitario');
            $table->string('precio_descontado');
            $table->string('descuento_cliente');
            $table->string('descuento_categoria');
            $table->string('descuento_producto');
            $table->unsignedBigInteger('dimension_id');
            $table->unsignedBigInteger('pedido_id');

            $table->foreign('dimension_id')->references('id')->on('dimensiones')->onDelete('cascade');
            $table->foreign('pedido_id')->references('id')->on('pedidos')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dimension_pedido');
    }
};
