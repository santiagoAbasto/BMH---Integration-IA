<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Campana y «Te esperan» del panel: qué pedidos todavía no se abrieron (pedidos.visto_at, se marca al entrar al
 * pedido) y qué clientes nuevos no se revisaron (users.revisado_at, se marca desde la campana o al habilitarlo).
 * Lo que ya existía cuenta como visto: la campana arranca con lo que llegue desde ahora. Aditiva.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pedidos', 'visto_at')) {
            Schema::table('pedidos', function (Blueprint $table): void {
                $table->timestamp('visto_at')->nullable()->after('estado_orden');
            });
            DB::table('pedidos')->whereNull('visto_at')->update(['visto_at' => DB::raw('COALESCE(created_at, NOW())')]);
        }

        if (! Schema::hasColumn('users', 'revisado_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->timestamp('revisado_at')->nullable();
            });
            DB::table('users')->whereNull('revisado_at')->update(['revisado_at' => DB::raw('COALESCE(created_at, NOW())')]);
        }
    }

    public function down(): void
    {
        Schema::table('pedidos', fn (Blueprint $table) => $table->dropColumn('visto_at'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('revisado_at'));
    }
};
