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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('orden')->default('zz');
            $table->string('codigo')->default('1');
            $table->string('nombre');
            $table->text('descripcion')->default('a');
            $table->longtext('caracteristicas')->nullable();
            $table->decimal('precio', 8, 2)->default(1);
            $table->boolean('iva')->default(true);
            $table->string('descuento')->default('0');
            $table->boolean('destacada')->default(false);
            $table->unsignedBigInteger('stock')->default(1);
            $table->unsignedBigInteger('categoria_id')->nullable();

            $table->foreign('categoria_id')->references('id')->on('categorias')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
