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
        Schema::create('imagenes', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('baner_texto')->nullable();
            $table->string('sector')->nullable();
            $table->integer('producto_id')->nullable();
            $table->string('tipo')->default('imagen');
            $table->string('orden')->default('aa');
            $table->string('posicion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imagenes');
    }
};
