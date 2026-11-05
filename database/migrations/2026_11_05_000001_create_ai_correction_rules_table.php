<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reglas de corrección aprobadas por una persona.
 *
 * No son entrenamiento automático del proveedor: sólo reutilizan una
 * corrección cuando la próxima consulta tiene la misma huella de entrada
 * (misma foto o mismo texto normalizado). Así un feedback equivocado no se
 * propaga y una corrección aprobada puede ayudar a otro cliente.
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

        if ($schema->hasTable('ai_correction_rules')) {
            return;
        }

        $schema->create('ai_correction_rules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('feedback_id')->unique();
            // SHA-256: no se guarda el texto ni la foto de otro cliente como
            // índice reutilizable de la regla.
            $table->string('input_fingerprint', 64)->index();
            $table->string('fingerprint_kind', 24); // image|text
            $table->unsignedBigInteger('expected_product_id')->index();
            $table->unsignedBigInteger('approved_by_admin_id');
            $table->timestamp('approved_at');
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->index(['input_fingerprint', 'active']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection())->dropIfExists('ai_correction_rules');
    }
};
