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
        Schema::create('nosotros_contenido', function (Blueprint $table) {
            $table->id();
            $table->text('info');
            $table->text('mision');
            $table->text('vision');
            $table->text('valores');
            $table->string('imagen_file');
            $table->string('titulo_home');
            $table->text('info_home');
            $table->string('imagen_file_home');
            $table->string('titulo_baner');
            $table->text('texto_baner');
            $table->string('imagen_file_kovea');
            $table->string('titulo_kovea');
            $table->text('texto_kovea');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nosotros_contenido');
    }
};
