<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ofertas de la zona pública (Figma: «ofertas»): un producto se marca o se desmarca desde el
 * listado de productos del admin. Es independiente de `descuento`, que baja el precio en la Zona
 * de Clientes (las ofertas de la zona privada todavía están en definición).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('productos', 'oferta')) {
            return;
        }

        Schema::table('productos', function (Blueprint $table): void {
            $table->boolean('oferta')->default(false)->after('destacada')->index();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('productos', 'oferta')) {
            Schema::table('productos', function (Blueprint $table): void {
                $table->dropIndex(['oferta']);
                $table->dropColumn('oferta');
            });
        }
    }
};
