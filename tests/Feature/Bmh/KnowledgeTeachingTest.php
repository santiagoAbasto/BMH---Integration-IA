<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Models\Ai\AiDemoAccount;
use App\Models\Ai\AiKnowledge;
use App\Models\User;
use App\Services\Ai\AiProviderManager;
use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\KnowledgeBaseService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Fase 2: enseñar al asesor CONVERSANDO desde el chat de una cuenta de prueba.
 *
 * Lo que se fija:
 *  - una cuenta demo enseña un término y queda PENDIENTE (no viaja al prompt
 *    hasta que un admin lo aprueba);
 *  - una cuenta sin demo no puede enseñar aunque tenga acceso al asesor;
 *  - un mensaje que no enseña nada no crea entrada.
 */
final class KnowledgeTeachingTest extends TestCase
{
    private User $customer;

    /** @var list<string> */
    private array $tables = ['ai_knowledge', 'ai_demo_accounts', 'ai_attachments', 'ai_messages', 'ai_conversations'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--path' => 'database/migrations/2026_11_19_000001_create_ai_knowledge_table.php', '--force' => true])
            ->assertSuccessful();

        foreach ($this->tables as $table) {
            DB::connection('mysql_ai')->table($table)->delete();
        }

        config(['bmh.assistant_access.mode' => 'allowlist', 'bmh.assistant_access.customer_ids' => []]);

        $row = DB::connection('mysql_legacy')->table('users')->where('habilitado', 1)->where('rol', 'cliente')->first();
        $this->assertNotNull($row);
        $this->customer = (new User())->setConnection('mysql_legacy')->newQuery()->findOrFail($row->id);
    }

    protected function tearDown(): void
    {
        foreach ($this->tables as $table) {
            DB::connection('mysql_ai')->table($table)->delete();
        }

        parent::tearDown();
    }

    private function makeDemo(): void
    {
        AiDemoAccount::query()->create(['customer_id' => (int) $this->customer->id, 'active' => true]);
    }

    /** @param array<string,mixed> $out */
    private function mockExtraction(array $out): void
    {
        $provider = $this->createMock(AiProviderInterface::class);
        $provider->method('isAvailable')->willReturn(true);
        $provider->method('name')->willReturn('faketest');
        $provider->method('structuredOutput')->willReturn($out);

        config(['bmh.features.ai' => true, 'bmh.ai.provider' => 'faketest']);
        app(AiProviderManager::class)->extend('faketest', $provider);
    }

    private function startConversation(): int
    {
        return (int) $this->actingAs($this->customer)
            ->postJson('/api/assistant/conversations')
            ->assertCreated()
            ->json('conversation.id');
    }

    public function test_una_cuenta_demo_ensena_un_termino_y_queda_pendiente(): void
    {
        $this->makeDemo();
        $this->mockExtraction([
            'is_teaching' => true,
            'kind' => 'term',
            'title' => 'arr.',
            'body' => '«arr.» es lo mismo que inducido o bobina de arranque.',
            'synonyms' => 'inducido, bobina de arranque',
        ]);

        $id = $this->startConversation();

        $this->actingAs($this->customer)
            ->postJson("/api/assistant/conversations/{$id}/teach", ['message' => 'arr es lo mismo que inducido'])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $row = AiKnowledge::query()->firstOrFail();
        $this->assertSame('term', $row->kind);
        $this->assertSame(AiKnowledge::STATUS_PENDING, $row->status);
        $this->assertSame('demo_chat', $row->source);
        $this->assertSame((int) $this->customer->id, $row->created_by);

        // Pendiente: no viaja al prompt hasta que un admin lo apruebe.
        $this->assertStringNotContainsString('inducido', app(KnowledgeBaseService::class)->promptSection());

        $row->update(['status' => AiKnowledge::STATUS_APPROVED]);
        $this->assertStringContainsString('inducido', app(KnowledgeBaseService::class)->promptSection());
    }

    public function test_sin_cuenta_demo_no_puede_ensenar(): void
    {
        // Tiene acceso al asesor por la lista, pero no es cuenta de prueba.
        config(['bmh.assistant_access.customer_ids' => [(int) $this->customer->id]]);
        $this->mockExtraction(['is_teaching' => true, 'kind' => 'term', 'title' => 'x', 'body' => 'y', 'synonyms' => '']);

        $id = $this->startConversation();

        $this->actingAs($this->customer)
            ->postJson("/api/assistant/conversations/{$id}/teach", ['message' => 'arr es inducido'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'not_demo');

        $this->assertSame(0, AiKnowledge::query()->count());
    }

    public function test_un_mensaje_que_no_ensena_no_crea_entrada(): void
    {
        $this->makeDemo();
        $this->mockExtraction(['is_teaching' => false]);

        $id = $this->startConversation();

        $this->actingAs($this->customer)
            ->postJson("/api/assistant/conversations/{$id}/teach", ['message' => '¿tenés un alternador para un Gol?'])
            ->assertOk()
            ->assertJsonPath('ok', false);

        $this->assertSame(0, AiKnowledge::query()->count());
    }
}
