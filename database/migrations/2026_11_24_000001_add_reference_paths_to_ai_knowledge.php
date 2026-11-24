<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enseñanza visual (Fase 4): una entrada de conocimiento puede guardar VARIAS
 * fotos de ejemplo, no una sola. Marcos manda dos fotos de una bobina para
 * mostrar la diferencia del colector; se guardan todas para compararlas después
 * contra la foto del cliente.
 *
 * `reference_path` (una sola) se conserva para la miniatura del admin y las
 * entradas viejas; `reference_paths` (lista) suma el resto.
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

        if (! $schema->hasTable('ai_knowledge') || $schema->hasColumn('ai_knowledge', 'reference_paths')) {
            return;
        }

        $schema->table('ai_knowledge', function (Blueprint $table): void {
            $table->json('reference_paths')->nullable()->after('reference_path');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection());

        if ($schema->hasColumn('ai_knowledge', 'reference_paths')) {
            $schema->table('ai_knowledge', function (Blueprint $table): void {
                $table->dropColumn('reference_paths');
            });
        }
    }
};
