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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('fecha');
            $table->string('nombre');
            $table->string('dni');
            $table->string('mail');
            $table->string('provincia');
            $table->string('localidad');
            $table->string('direccion');
            $table->string('celular');
            $table->string('cp');

            $table->string('direccion2')->nullable();
            $table->string('provincia2')->nullable();
            $table->string('localidad2')->nullable();
            $table->string('cp2')->nullable();

            $table->string('tipo_envio');
            $table->string('tipo_pago');
            // $table->string('costo_envio');
            $table->string('descuento_cliente');
            $table->string('descuento_pago');
            
            $table->string('total_pedido');
            $table->boolean('archivo')->default(false);
            $table->text('notas')->nullable();
            $table->string('estado');
            $table->string('estado_orden')->default('Pendiente');
            $table->string('vendedor')->nullable();

            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->foreign('cliente_id')->references('id')->on('users')->onDelete('set null');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
