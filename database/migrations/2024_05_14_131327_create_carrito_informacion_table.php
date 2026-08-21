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
        Schema::create('carrito_informacion', function (Blueprint $table) {
            $table->id();
            $table->text('info');
            $table->text('pedido');
            $table->string('pedido_titulo');
            $table->text('info_efectivo')->nullable();
            $table->longtext('info_mp')->nullable();
            $table->text('info_retiro')->nullable();
            $table->text('info_convenir')->nullable();
            $table->string('descuento_efectivo')->default('10');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carrito_informacion');
    }
};
