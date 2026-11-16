<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La oferta deja de ser sí/no y pasa a tener alcance (App\Enums\AlcanceOferta):
 *  - null: no está en oferta;
 *  - 'publica': se ve en el sitio público (Ofertas) y en la Zona de Clientes;
 *  - 'clientes': sólo la ven los clientes con sesión.
 * Las que ya estaban en oferta quedan 'publica' (era lo único que había). El índice se conserva.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('productos', 'oferta')) {
            return;
        }

        DB::statement('ALTER TABLE productos MODIFY oferta VARCHAR(10) NULL DEFAULT NULL');
        DB::table('productos')->where('oferta', '1')->update(['oferta' => 'publica']);
        DB::table('productos')->whereIn('oferta', ['0', ''])->update(['oferta' => null]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('productos', 'oferta')) {
            return;
        }

        // Las de «sólo clientes» vuelven como oferta común (antes no se distinguía).
        DB::table('productos')->whereNotNull('oferta')->update(['oferta' => '1']);
        DB::table('productos')->whereNull('oferta')->update(['oferta' => '0']);
        DB::statement('ALTER TABLE productos MODIFY oferta TINYINT(1) NOT NULL DEFAULT 0');
    }
};
