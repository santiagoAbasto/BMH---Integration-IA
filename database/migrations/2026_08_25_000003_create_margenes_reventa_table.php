<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Márgenes de reventa por cliente y categoría.
     *
     * El cliente tiene un margen "general" en `users.reventa`. Esta tabla
     * guarda excepciones por categoría: si existe una fila para
     * (user_id, categoria_id) se usa ESA en lugar del general.
     */
    public function up(): void
    {
        Schema::create('margenes_reventa', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('categoria_id');
            $table->decimal('porcentaje', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'categoria_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('categoria_id')->references('id')->on('categorias')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('margenes_reventa');
    }
};
