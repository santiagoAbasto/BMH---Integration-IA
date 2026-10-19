<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El carrito de cada cliente (o vendedor) en la base, para verlo igual
     * desde cualquier dispositivo. Una fila por usuario; el contenido es la
     * lista de ítems en JSON (ver App\Services\Carrito\CarritoCompartido).
     */
    public function up(): void
    {
        if (Schema::hasTable('carritos_clientes')) {
            return;
        }

        Schema::create('carritos_clientes', function (Blueprint $table): void {
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->json('contenido');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carritos_clientes');
    }
};
