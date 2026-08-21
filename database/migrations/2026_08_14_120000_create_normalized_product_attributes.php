<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atributos_producto', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('legacy_slot')->unique();
            $table->string('clave', 128)->unique();
            $table->string('nombre');
            $table->string('tipo', 32);
            $table->string('unidad', 32)->nullable();
            $table->boolean('semantica_confirmada')->default(true);
            $table->timestamps();
        });

        Schema::create('atributo_producto_categoria', function (Blueprint $table): void {
            $table->foreignId('atributo_id')->constrained('atributos_producto')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['atributo_id', 'categoria_id']);
        });

        Schema::create('producto_atributo', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('atributo_id')->constrained('atributos_producto')->cascadeOnDelete();
            $table->longText('valor');
            $table->string('origen', 32)->default('legacy_slot');
            $table->char('valor_hash', 64);
            $table->timestamps();
            $table->unique(['producto_id', 'atributo_id']);
            $table->index(['atributo_id', 'producto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_atributo');
        Schema::dropIfExists('atributo_producto_categoria');
        Schema::dropIfExists('atributos_producto');
    }
};
