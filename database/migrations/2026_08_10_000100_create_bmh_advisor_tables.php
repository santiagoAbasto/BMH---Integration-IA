<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Base propia del asesor.
 *
 * Vive en la conexión `mysql_ai`, completamente separada de la base legacy de
 * BMH. Ninguna de estas tablas tiene foreign key contra `productos` o `users`:
 * el vínculo es por id, sin constraint, justamente para no acoplar el esquema
 * nuevo al viejo ni impedir que la legacy se migre más adelante.
 *
 * @see docs/architecture.md §"Base propia del asistente"
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

        // Una conversación del asesor con un cliente.
        $schema->create('ai_conversations', function (Blueprint $table): void {
            $table->id();
            // Sin FK: `users` vive en la base legacy.
            $table->unsignedBigInteger('customer_id')->index();
            $table->string('title')->nullable();
            $table->string('status', 32)->default('active');   // active|resolved|handed_off
            $table->string('detected_intent', 64)->nullable();
            $table->unsignedBigInteger('resolved_product_id')->nullable();
            $table->string('prompt_version', 64)->nullable();
            $table->unsignedInteger('total_input_tokens')->default(0);
            $table->unsignedInteger('total_output_tokens')->default(0);
            $table->unsignedInteger('total_images')->default(0);
            $table->timestamps();

            $table->index(['customer_id', 'updated_at']);
        });

        $schema->create('ai_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->string('role', 16);          // user|assistant|system
            $table->longText('content');
            // Estado de la búsqueda cuando se emitió el mensaje. Sin
            // chain-of-thought: sólo hechos y decisiones.
            $table->json('metadata')->nullable();
            $table->string('provider', 32)->nullable();
            $table->string('model', 64)->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'id']);
        });

        $schema->create('ai_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('ai_messages')->nullOnDelete();
            $table->string('disk', 32);
            $table->string('path');              // original, en disco privado
            $table->string('analysis_path')->nullable();  // versión optimizada para el modelo
            $table->string('thumbnail_path')->nullable();
            $table->string('mime', 64);
            $table->unsignedInteger('bytes');
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->json('analysis')->nullable(); // salida de ImageAnalysis
            $table->timestamps();
        });

        // Auditoría de qué tools se ejecutaron. Permite reconstruir una
        // conversación sin guardar razonamiento del modelo.
        $schema->create('ai_message_tool_calls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')->constrained('ai_messages')->cascadeOnDelete();
            $table->string('tool', 64);
            $table->json('arguments')->nullable();
            $table->json('result_summary')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->boolean('ok')->default(true);
            $table->timestamps();

            $table->index('tool');
        });

        // Candidatos que se le mostraron al cliente en un momento dado.
        $schema->create('ai_product_candidates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')->constrained('ai_messages')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->string('product_code', 64)->nullable();
            $table->decimal('confidence', 5, 4);
            $table->string('confidence_band', 16);
            $table->json('signals')->nullable();
            $table->unsignedSmallInteger('position');
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
        });

        // Hechos acumulados de la conversación: confirmados vs. inferidos.
        $schema->create('ai_customer_context', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->string('fact_key', 64);
            $table->string('fact_value', 255);
            // confirmed|inferred|unknown — una inferencia nunca se promueve sola.
            $table->string('state', 16)->default('inferred');
            $table->string('source', 64);
            $table->decimal('confidence', 5, 4)->default(0);
            $table->timestamps();

            $table->unique(['conversation_id', 'fact_key']);
        });

        $schema->create('ai_feedback', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('ai_messages')->nullOnDelete();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->boolean('was_correct');
            $table->string('comment', 500)->nullable();
            $table->timestamps();
        });

        $schema->create('ai_handoffs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('seller_id')->nullable();
            $table->string('reason', 64);
            $table->text('summary');
            $table->json('context')->nullable();
            $table->string('status', 32)->default('pending'); // pending|claimed|closed
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        $schema->create('ai_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('conversation_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('event', 64);
            $table->string('provider', 32)->nullable();
            $table->string('model', 64)->nullable();
            $table->string('prompt_version', 64)->nullable();
            $table->json('payload')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->timestamps();

            $table->index(['event', 'created_at']);
        });

        /*
         * Índice de búsqueda materializado.
         *
         * La base legacy no tiene índice sobre `codigo`, `marca` ni `nombre`, y
         * no la vamos a tocar. Este documento normalizado es nuestra copia
         * indexable, reconstruida por `php artisan bmh:catalog-sync`.
         */
        $schema->create('catalog_search_documents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id')->unique();
            $table->string('code', 128)->index();
            $table->string('normalized_code', 128)->index();
            $table->string('name', 512);
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->string('category_name', 255)->nullable();
            $table->string('brand', 255)->nullable()->index();
            $table->string('model', 512)->nullable();
            $table->string('condition', 16)->nullable();
            $table->decimal('list_price', 12, 2)->nullable();
            $table->boolean('has_image')->default(false);
            $table->boolean('duplicate_code')->default(false);
            $table->json('attributes')->nullable();
            $table->json('equivalences')->nullable();
            $table->longText('searchable_text');
            // Hash del contenido: permite que el sync sea idempotente y sólo
            // reescriba lo que cambió.
            $table->string('content_hash', 64)->index();
            $table->timestamps();
        });

        // FULLTEXT no se puede declarar con el Blueprint estándar en todas las
        // versiones; se agrega explícito.
        Schema::connection($this->connection())->getConnection()->statement(
            'ALTER TABLE catalog_search_documents ADD FULLTEXT ft_searchable (searchable_text, name)'
        );

        $schema->create('catalog_sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('status', 32)->default('running'); // running|completed|failed
            $table->unsignedInteger('products_read')->default(0);
            $table->unsignedInteger('documents_created')->default(0);
            $table->unsignedInteger('documents_updated')->default(0);
            $table->unsignedInteger('documents_deleted')->default(0);
            $table->unsignedInteger('anomalies')->default(0);
            $table->json('report')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection());

        foreach ([
            'catalog_sync_runs',
            'catalog_search_documents',
            'ai_audit_logs',
            'ai_handoffs',
            'ai_feedback',
            'ai_customer_context',
            'ai_product_candidates',
            'ai_message_tool_calls',
            'ai_attachments',
            'ai_messages',
            'ai_conversations',
        ] as $table) {
            $schema->dropIfExists($table);
        }
    }
};
