<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('mysql_ai');

        if ($schema->hasTable('ai_cart_operations')) {
            return;
        }

        $schema->create('ai_cart_operations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained('ai_messages')->nullOnDelete();
            $table->foreignId('feedback_id')->unique()->constrained('ai_feedback')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedSmallInteger('quantity');
            $table->string('status', 24)->default('pending');
            $table->json('result')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_ai')->dropIfExists('ai_cart_operations');
    }
};
