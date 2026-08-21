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
        Schema::create('dimensiones', function (Blueprint $table) {
            $table->id();
            $table->string('diametro');
            $table->string('largo')->nullable();
            $table->string('altura_cuadrado')->nullable();
            $table->string('altura_cabeza')->nullable();
            $table->string('diametro_cabeza')->nullable();
            $table->decimal('precio', 8, 2)->default(1);
            $table->integer('unidad')->default(1);

            $table->unsignedBigInteger('producto_id');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dimensiones');
    }
};
