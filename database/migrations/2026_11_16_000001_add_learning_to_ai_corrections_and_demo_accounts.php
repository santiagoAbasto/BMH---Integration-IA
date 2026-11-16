<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aprendizaje del asesor a partir de pruebas.
 *
 * - Las reglas dejan de exigir la misma foto byte a byte: guardan una huella
 *   perceptual, así la misma captura reenviada o recomprimida se reconoce.
 * - Una regla puede nacer de una enseñanza del administrador (sin feedback) o
 *   de una cuenta demo de confianza, no sólo de un feedback aprobado a mano.
 * - Cuentas demo: clientes habilitados para probar el asesor y evaluarlo.
 *   Reemplaza editar AI_ALLOWED_CUSTOMER_IDS en el .env del servidor.
 *
 * Sólo agrega columnas/tablas en la base del asesor. Idempotente.
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
            $columns = [
                'average_hash' => static fn (Blueprint $t) => $t->string('average_hash', 16)->nullable(),
                'difference_hash' => static fn (Blueprint $t) => $t->string('difference_hash', 16)->nullable(),
                // feedback | admin_teach | demo_account
                'source' => static fn (Blueprint $t) => $t->string('source', 16)->default('feedback')->index(),
                'note' => static fn (Blueprint $t) => $t->string('note', 255)->nullable(),
                'reference_path' => static fn (Blueprint $t) => $t->string('reference_path', 255)->nullable(),
                'hits' => static fn (Blueprint $t) => $t->unsignedInteger('hits')->default(0),
                'last_hit_at' => static fn (Blueprint $t) => $t->timestamp('last_hit_at')->nullable(),
            ];

            foreach ($columns as $column => $definition) {
                if (! $schema->hasColumn('ai_correction_rules', $column)) {
                    $schema->table('ai_correction_rules', function (Blueprint $table) use ($definition): void {
                        $definition($table);
                    });
                }
            }

            // Una enseñanza del administrador no viene de un feedback, y una
            // regla de una cuenta demo no tiene un administrador que apruebe.
            // Laravel 10 sin doctrine/dbal no puede ->change(): SQL directo.
            $db = DB::connection($this->connection());
            $db->statement('ALTER TABLE ai_correction_rules MODIFY feedback_id BIGINT UNSIGNED NULL');
            $db->statement('ALTER TABLE ai_correction_rules MODIFY approved_by_admin_id BIGINT UNSIGNED NULL');
        }

        if (! $schema->hasTable('ai_demo_accounts')) {
            $schema->create('ai_demo_accounts', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('customer_id')->unique();
                $table->string('label', 80)->nullable();
                $table->boolean('active')->default(true)->index();
                $table->unsignedBigInteger('created_by_admin_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection());

        $schema->dropIfExists('ai_demo_accounts');

        if ($schema->hasTable('ai_correction_rules')) {
            foreach (['last_hit_at', 'hits', 'reference_path', 'note', 'source', 'difference_hash', 'average_hash'] as $column) {
                if ($schema->hasColumn('ai_correction_rules', $column)) {
                    $schema->table('ai_correction_rules', function (Blueprint $table) use ($column): void {
                        $table->dropColumn($column);
                    });
                }
            }
        }
    }
};
