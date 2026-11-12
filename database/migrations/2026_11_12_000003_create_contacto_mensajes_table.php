<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin → Contacto (como en Conelec). Aditiva:
 * - `contacto_mensajes`: cada consulta del formulario de /contacto queda guardada (antes sólo se mandaba por mail),
 *   para leerla en Contacto › Mensajes aunque el mail no haya salido.
 * - `contacto` suma a qué emails llegan los avisos, el asunto del aviso y si se le manda un acuse a quien escribe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contacto_mensajes')) {
            Schema::create('contacto_mensajes', function (Blueprint $table): void {
                $table->id();
                $table->string('nombre', 120);
                $table->string('email', 150);
                $table->string('telefono', 40)->nullable();
                $table->string('empresa', 120)->nullable();
                $table->string('motivo', 80)->nullable();
                $table->text('mensaje')->nullable();
                $table->timestamp('leido_at')->nullable();
                $table->timestamp('aviso_enviado_at')->nullable();
                $table->string('ip', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->timestamps();

                $table->index('created_at');
                $table->index('leido_at');
            });
        }

        Schema::table('contacto', function (Blueprint $table): void {
            if (! Schema::hasColumn('contacto', 'emails_aviso')) {
                $table->string('emails_aviso', 500)->nullable()->after('facebook');
            }
            if (! Schema::hasColumn('contacto', 'asunto_aviso')) {
                $table->string('asunto_aviso', 150)->nullable()->after('emails_aviso');
            }
            if (! Schema::hasColumn('contacto', 'acuse_recibo')) {
                $table->boolean('acuse_recibo')->default(true)->after('asunto_aviso');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacto_mensajes');

        foreach (['acuse_recibo', 'asunto_aviso', 'emails_aviso'] as $columna) {
            if (Schema::hasColumn('contacto', $columna)) {
                Schema::table('contacto', fn (Blueprint $table) => $table->dropColumn($columna));
            }
        }
    }
};
