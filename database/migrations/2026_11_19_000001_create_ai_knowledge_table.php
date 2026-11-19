<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Base de conocimiento del asesor: términos, reglas y notas en lenguaje natural
 * que el equipo de BMH enseña y que se inyectan al prompt del modelo.
 *
 * A diferencia de ai_correction_rules (foto/texto → código, un atajo que empuja
 * el ranking), esto es conocimiento que el modelo LEE y aplica: equivalencias
 * ("arr. = inducido = bobina de arranque"), reglas del negocio y notas de rubro
 * o producto. Sólo lo aprobado y activo viaja al prompt; una persona lo aprueba
 * antes de que afecte a todos los clientes, y se desactiva al instante.
 */
return new class extends Migration
{
    private function connection(): string
    {
        return 'mysql_ai';
    }

    public function up(): void
    {
        $schema = Schema::connection($this->connection());

        if ($schema->hasTable('ai_knowledge')) {
            return;
        }

        $schema->create('ai_knowledge', function (Blueprint $table): void {
            $table->id();
            $table->string('kind', 16);                 // term | rule | note
            $table->string('title', 120);
            $table->text('body');
            // Sólo informativo en la Fase 1; la búsqueda lo usa en la Fase 2.
            $table->string('synonyms', 255)->nullable();
            // Acotan la entrada: global si ambas son null.
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            // Imagen opcional de apoyo, en el disco ai_private (como las reglas).
            $table->string('reference_path')->nullable();
            $table->string('status', 12)->default('pending')->index(); // pending|approved|rejected
            $table->string('source', 16)->default('admin');            // admin|demo_chat|feedback
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by_admin_id')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            // El prompt trae lo aprobado + activo; este índice sirve esa consulta.
            $table->index(['status', 'active']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection())->dropIfExists('ai_knowledge');
    }
};
