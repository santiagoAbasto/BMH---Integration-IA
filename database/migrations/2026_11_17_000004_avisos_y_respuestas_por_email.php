<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Emails del panel:
 * - contacto.emails_aviso_registro / emails_aviso_pedidos: a qué cuentas de BMH llega el aviso de un cliente nuevo y
 *   el de un pedido nuevo (vacías, al mismo destino que las consultas de Contacto).
 * - contacto_respuestas: las respuestas que se escriben desde Contacto › Mensajes; quedan en la conversación aunque el
 *   email no salga (enviado_at / fallido_at) para volver a intentarlo.
 * Aditiva.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacto', function (Blueprint $table): void {
            if (! Schema::hasColumn('contacto', 'emails_aviso_registro')) {
                $table->string('emails_aviso_registro', 500)->nullable()->after('acuse_recibo');
            }
            if (! Schema::hasColumn('contacto', 'emails_aviso_pedidos')) {
                $table->string('emails_aviso_pedidos', 500)->nullable()->after('emails_aviso_registro');
            }
        });

        if (! Schema::hasTable('contacto_respuestas')) {
            Schema::create('contacto_respuestas', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('contacto_mensaje_id')->constrained('contacto_mensajes')->cascadeOnDelete();
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->string('autor', 120)->nullable();
                $table->string('asunto', 200);
                $table->text('cuerpo');
                $table->timestamp('enviado_at')->nullable();
                $table->timestamp('fallido_at')->nullable();
                $table->timestamps();
                $table->index(['contacto_mensaje_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contacto_respuestas');
        Schema::table('contacto', function (Blueprint $table): void {
            $table->dropColumn(['emails_aviso_registro', 'emails_aviso_pedidos']);
        });
    }
};
