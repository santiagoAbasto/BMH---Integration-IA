<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Una respuesta marcada con "No" no se convierte en verdad automáticamente.
 * Se conserva el motivo y el código esperado como una corrección verificable
 * para que un administrador pueda revisarla antes de ajustar catálogo/reglas.
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

        if (! $schema->hasTable('ai_feedback')) {
            return;
        }

        $columns = [
            'expected_product_id' => static fn (Blueprint $table) => $table->unsignedBigInteger('expected_product_id')->nullable(),
            'expected_code' => static fn (Blueprint $table) => $table->string('expected_code', 64)->nullable(),
            'reason' => static fn (Blueprint $table) => $table->string('reason', 500)->nullable(),
            'review_status' => static fn (Blueprint $table) => $table->string('review_status', 24)->default('pending')->index(),
            'reviewed_by_admin_id' => static fn (Blueprint $table) => $table->unsignedBigInteger('reviewed_by_admin_id')->nullable(),
            'reviewed_at' => static fn (Blueprint $table) => $table->timestamp('reviewed_at')->nullable(),
            'admin_note' => static fn (Blueprint $table) => $table->string('admin_note', 1000)->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if ($schema->hasColumn('ai_feedback', $column)) {
                continue;
            }

            $schema->table('ai_feedback', function (Blueprint $table) use ($definition): void {
                $definition($table);
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection());

        if (! $schema->hasTable('ai_feedback')) {
            return;
        }

        foreach ([
            'admin_note', 'reviewed_at', 'reviewed_by_admin_id', 'review_status',
            'reason', 'expected_code', 'expected_product_id',
        ] as $column) {
            if ($schema->hasColumn('ai_feedback', $column)) {
                $schema->table('ai_feedback', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
