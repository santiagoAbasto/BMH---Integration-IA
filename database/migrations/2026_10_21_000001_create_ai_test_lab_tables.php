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

        if (! $schema->hasColumn('ai_conversations', 'mode')) {
            $schema->table('ai_conversations', function (Blueprint $table): void {
                $table->string('mode', 16)->default('customer')->index()->after('customer_id');
            });
        }

        if (! $schema->hasColumn('ai_conversations', 'tester_admin_id')) {
            $schema->table('ai_conversations', function (Blueprint $table): void {
                $table->unsignedBigInteger('tester_admin_id')->nullable()->index()->after('mode');
            });
        }

        if (! $schema->hasColumn('ai_conversations', 'test_family')) {
            $schema->table('ai_conversations', function (Blueprint $table): void {
                $table->string('test_family', 80)->nullable()->after('tester_admin_id');
            });
        }

        if (! $schema->hasTable('ai_test_reviews')) {
            $schema->create('ai_test_reviews', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
                $table->foreignId('message_id')->nullable()->constrained('ai_messages')->nullOnDelete();
                // `admins` vive en la base principal, por eso no hay FK entre conexiones.
                $table->unsignedBigInteger('admin_id')->index();
                $table->string('family', 80);
                $table->string('rating', 24);
                $table->string('expected_question_id', 120)->nullable();
                $table->json('issue_tags')->nullable();
                $table->text('comment')->nullable();
                $table->string('question_bank_version', 64);
                $table->string('prompt_version', 64)->nullable();
                $table->string('provider', 32)->nullable();
                $table->string('model', 64)->nullable();
                $table->timestamps();

                $table->index(['conversation_id', 'created_at']);
                $table->index(['family', 'rating']);
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection());

        $schema->dropIfExists('ai_test_reviews');

        if ($schema->hasColumn('ai_conversations', 'test_family')) {
            $schema->table('ai_conversations', function (Blueprint $table): void {
                $table->dropColumn('test_family');
            });
        }

        if ($schema->hasColumn('ai_conversations', 'tester_admin_id')) {
            $schema->table('ai_conversations', function (Blueprint $table): void {
                $table->dropColumn('tester_admin_id');
            });
        }

        if ($schema->hasColumn('ai_conversations', 'mode')) {
            $schema->table('ai_conversations', function (Blueprint $table): void {
                $table->dropColumn('mode');
            });
        }
    }
};
