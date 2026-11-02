<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function connection(): string
    {
        return 'mysql_ai';
    }

    public function up(): void
    {
        $schema = Schema::connection($this->connection());

        if (! $schema->hasTable('ai_test_reviews')) {
            return;
        }

        $columns = [
            'expected_action' => static fn (Blueprint $table) => $table->string('expected_action', 32)->nullable(),
            'expected_product_id' => static fn (Blueprint $table) => $table->unsignedBigInteger('expected_product_id')->nullable(),
            'expected_code' => static fn (Blueprint $table) => $table->string('expected_code', 64)->nullable(),
            'surface' => static fn (Blueprint $table) => $table->string('surface', 16)->default('private'),
            'severity' => static fn (Blueprint $table) => $table->string('severity', 16)->default('medium'),
        ];

        // Cada columna se agrega por separado: si una ejecución anterior quedó
        // a medias, la migración completa únicamente lo que todavía falta.
        foreach ($columns as $column => $definition) {
            if ($schema->hasColumn('ai_test_reviews', $column)) {
                continue;
            }

            $schema->table('ai_test_reviews', function (Blueprint $table) use ($definition): void {
                $definition($table);
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection());

        if (! $schema->hasTable('ai_test_reviews')) {
            return;
        }

        foreach (['severity', 'surface', 'expected_code', 'expected_product_id', 'expected_action'] as $column) {
            if (! $schema->hasColumn('ai_test_reviews', $column)) {
                continue;
            }

            $schema->table('ai_test_reviews', function (Blueprint $table) use ($column): void {
                $table->dropColumn($column);
            });
        }
    }
};
