<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Descuento de la oferta (admin → Productos, al ponerlo en oferta):
 *  - oferta_tipo: 'porcentaje' (oferta_valor = % sobre el precio de lista) o 'precio' (oferta_valor = precio de lista de
 *    la oferta); null = la oferta no cambia el precio (sólo se destaca).
 * La oferta reemplaza el precio de lista para los clientes con sesión; los demás descuentos se aplican igual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            if (! Schema::hasColumn('productos', 'oferta_tipo')) {
                $table->string('oferta_tipo', 10)->nullable()->after('oferta');
            }
            if (! Schema::hasColumn('productos', 'oferta_valor')) {
                $table->decimal('oferta_valor', 12, 2)->nullable()->after('oferta_tipo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            $table->dropColumn(array_values(array_filter(['oferta_tipo', 'oferta_valor'], fn ($c) => Schema::hasColumn('productos', $c))));
        });
    }
};
