<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Biblioteca de imágenes de productos: cada archivo de public/imagenes que
     * se usa en productos (o que se subió para usar) tiene un código único y
     * editable. Ese código es el que se escribe en la columna IMÁGENES del
     * Excel de productos. Qué producto usa qué imagen sigue en la tabla
     * imagenes (por path): el sitio no depende de esta tabla.
     */
    public function up(): void
    {
        if (Schema::hasTable('biblioteca_imagenes')) {
            return;
        }

        Schema::create('biblioteca_imagenes', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 60)->unique();
            $table->string('path')->unique();
            $table->string('nombre_original')->nullable();
            $table->unsignedBigInteger('bytes')->nullable();
            $table->unsignedInteger('ancho')->nullable();
            $table->unsignedInteger('alto')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biblioteca_imagenes');
    }
};
