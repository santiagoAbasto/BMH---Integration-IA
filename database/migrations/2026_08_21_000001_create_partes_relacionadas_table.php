<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partes_relacionadas', function (Blueprint $table): void {
            $table->id();
            // El producto que declara la relación.
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            // La parte en sí: también es un producto del catálogo.
            $table->foreignId('parte_id')->constrained('productos')->cascadeOnDelete();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->unique(['producto_id', 'parte_id']);
            $table->index(['producto_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partes_relacionadas');
    }
};
