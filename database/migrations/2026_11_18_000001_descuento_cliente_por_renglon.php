<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El descuento del cliente por renglón del pedido. Los productos con precio de oferta se cobran a ese precio, sin el
 * descuento del cliente (BMH, 18/11/2026): esos renglones guardan 0. NULL (todos los pedidos de antes) = el descuento
 * del pedido (pedidos.descuento_cliente), como hasta ahora. Sólo agrega una columna: no cambia ningún pedido.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pedido_producto', 'descuento_cliente')) {
            Schema::table('pedido_producto', function (Blueprint $table): void {
                $table->decimal('descuento_cliente', 5, 2)->nullable()->after('descuento_producto');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pedido_producto', 'descuento_cliente')) {
            Schema::table('pedido_producto', fn (Blueprint $table) => $table->dropColumn('descuento_cliente'));
        }
    }
};
