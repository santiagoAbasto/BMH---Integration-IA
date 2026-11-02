<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Domain\Catalog\Contracts\CatalogRepositoryInterface;
use App\Domain\Customer\Contracts\CustomerRepositoryInterface;
use App\Models\Admin;
use App\Models\Ai\AiAttachment;
use App\Models\Ai\AiConversation;
use App\Models\Ai\AiHandoff;
use App\Models\Ai\AiMessageRecord;
use App\Models\Ai\AiProductCandidate;
use App\Models\Ai\AiTestReview;
use App\Models\User;
use App\Services\Ai\DiagnosticQuestionBank;
use App\Services\Ai\HumanHandoffService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AiTestLabTest extends TestCase
{
    private Admin $admin;

    private User $customer;

    private string $family;

    private ?int $logoFixtureId = null;

    protected function setUp(): void
    {
        parent::setUp();

        // La base del asesor es persistente y separada de la conexión que
        // Laravel refresca en tests. Aplicamos sólo esta migración idempotente
        // para que una copia de pruebas ya creada reciba las columnas nuevas.
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_10_21_000002_add_structured_expectations_to_ai_test_reviews.php',
            '--force' => true,
        ])->assertSuccessful();

        foreach (['ai_test_reviews', 'ai_audit_logs', 'ai_handoffs', 'ai_feedback', 'ai_customer_context',
            'ai_product_candidates', 'ai_message_tool_calls', 'ai_attachments',
            'ai_messages', 'ai_conversations'] as $table) {
            DB::connection('mysql_ai')->table($table)->delete();
        }

        $row = DB::connection('mysql_legacy')->table('users')
            ->where('habilitado', 1)
            ->where('rol', 'cliente')
            ->first();
        $this->assertNotNull($row);

        $this->customer = (new User())
            ->setConnection('mysql_legacy')
            ->newQuery()
            ->findOrFail($row->id);

        $this->admin = new Admin(['name' => 'Tester IA', 'rol' => 'administrador']);
        $this->admin->id = 99001;
        $this->family = $this->firstFamily();

        $logo = DB::table('imagenes')->where('sector', 'logo2')->first();

        if ($logo === null) {
            $this->logoFixtureId = (int) DB::table('imagenes')->insertGetId([
                'path' => 'logo2-test.png',
                'sector' => 'logo2',
            ]);
        } elseif ($logo->path === 'logo2-test.png') {
            $this->logoFixtureId = (int) $logo->id;
        }

        config(['bmh.testing.customer_id' => $this->customer->id]);
    }

    protected function tearDown(): void
    {
        if ($this->logoFixtureId !== null) {
            DB::table('imagenes')->where('id', $this->logoFixtureId)->delete();
        }

        parent::tearDown();
    }

    public function test_el_laboratorio_requiere_sesion_administrativa(): void
    {
        $this->get('/dashboard/ia/pruebas')->assertRedirect('/login/admin');

        $this->actingAs($this->customer, 'web')
            ->postJson('/dashboard/ia/pruebas/conversations', ['family' => $this->family])
            ->assertRedirect('/login/admin');
    }

    public function test_un_admin_ve_el_banco_de_preguntas_en_el_laboratorio(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get('/dashboard/ia/pruebas')
            ->assertOk()
            ->assertSee('Pruebas del asesor IA')
            ->assertSee('Preguntas esperadas')
            ->assertSee(app(DiagnosticQuestionBank::class)->version());
    }

    public function test_un_usuario_interno_sin_rol_administrador_no_accede_al_laboratorio(): void
    {
        $internalUser = new Admin(['name' => 'Usuario interno', 'rol' => 'usuario']);
        $internalUser->id = 99003;

        $this->actingAs($internalUser, 'admin')
            ->get('/dashboard/ia/pruebas')
            ->assertForbidden();

        $this->actingAs($internalUser, 'admin')
            ->postJson('/dashboard/ia/pruebas/conversations', ['family' => $this->family])
            ->assertForbidden();
    }

    public function test_un_admin_inicia_una_prueba_separada_de_los_chats_de_clientes(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->postJson('/dashboard/ia/pruebas/conversations', [
                'family' => $this->family,
                'customer_id' => $this->customer->id,
            ])
            ->assertCreated()
            ->assertJsonPath('conversation.family', $this->family);

        $conversation = AiConversation::query()->findOrFail((int) $response->json('conversation.id'));
        $this->assertSame('test', $conversation->mode);
        $this->assertSame($this->admin->id, $conversation->tester_admin_id);

        // Aunque use un cliente real como contexto comercial, nunca aparece en
        // la Zona de Clientes ni puede abrirse desde su API.
        $this->actingAs($this->customer, 'web')
            ->getJson('/api/assistant/conversations')
            ->assertOk()
            ->assertJsonMissing(['id' => $conversation->id]);
        $this->actingAs($this->customer, 'web')
            ->getJson("/api/assistant/conversations/{$conversation->id}")
            ->assertNotFound();
    }

    public function test_un_admin_no_puede_abrir_la_prueba_de_otro(): void
    {
        $conversation = $this->testConversation(testerAdminId: 12345);

        $this->actingAs($this->admin, 'admin')
            ->getJson("/dashboard/ia/pruebas/conversations/{$conversation->id}")
            ->assertNotFound();
    }

    public function test_un_admin_puede_enviar_un_mensaje_por_el_flujo_real_del_asesor(): void
    {
        $conversation = $this->testConversation();

        $response = $this->actingAs($this->admin, 'admin')
            ->postJson("/dashboard/ia/pruebas/conversations/{$conversation->id}/messages", [
                'message' => 'Necesito identificar un rotor de 12V.',
            ])
            ->assertOk()
            ->assertJsonPath('message.role', 'assistant');

        $this->assertIsInt($response->json('message.id'));
        $this->assertSame(2, AiMessageRecord::query()
            ->where('conversation_id', $conversation->id)
            ->count());
    }

    public function test_al_reabrir_una_prueba_rehidrata_las_mismas_cards_limitadas_del_turno(): void
    {
        $conversation = $this->testConversation();
        $message = AiMessageRecord::query()->create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Encontré estas alternativas.',
            'metadata' => ['candidate_count' => 5],
        ]);
        $limit = (int) config('bmh.ranking.max_presented', 3);
        $ids = DB::connection('mysql_legacy')->table('productos')
            ->whereNotNull('codigo')
            ->orderBy('id')
            ->limit($limit + 2)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
        $products = app(CatalogRepositoryInterface::class)->findMany($ids);
        $this->assertCount($limit + 2, $products);

        $bands = ['very_high', 'high', 'ambiguous', 'low', 'low'];
        $confidences = [0.95, 0.80, 0.60, 0.30, 0.20];

        foreach ($products as $position => $product) {
            AiProductCandidate::query()->create([
                'message_id' => $message->id,
                'product_id' => $product->id,
                'product_code' => $product->code,
                'confidence' => $confidences[$position],
                'confidence_band' => $bands[$position],
                'signals' => ['catalog_match' => 10.0 - $position],
                'position' => $position,
            ]);
        }

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/dashboard/ia/pruebas/conversations/{$conversation->id}")
            ->assertOk()
            ->assertJsonCount($limit, 'messages.0.candidates')
            ->assertJsonPath('messages.0.candidate_count', 5);

        $cards = $response->json('messages.0.candidates');

        foreach (array_slice($products, 0, $limit) as $position => $product) {
            $card = $cards[$position];

            $this->assertSame($product->id, $card['product']['id']);
            $this->assertSame($product->code, $card['product']['code']);
            $this->assertSame($product->name, $card['product']['name']);
            $this->assertSame($bands[$position], $card['confidence_band']);
            $this->assertSame(config('bmh.confidence.labels.'.$bands[$position]), $card['confidence_label']);
            $this->assertSame(config('bmh.confidence.short_labels.'.$bands[$position]), $card['confidence_short']);
            $this->assertArrayHasKey('price', $card);
            $this->assertArrayNotHasKey('product_id', $card);
            $this->assertArrayNotHasKey('product_code', $card);
        }
    }

    public function test_guarda_feedback_interno_con_versiones_y_exige_comentario_si_hay_un_problema(): void
    {
        $conversation = $this->testConversation();
        $message = AiMessageRecord::query()->create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => '¿Tenés algún código grabado?',
            'provider' => 'mock',
            'model' => 'mock-deterministic',
        ]);

        $endpoint = "/dashboard/ia/pruebas/conversations/{$conversation->id}/reviews";

        $productId = (int) DB::connection('mysql_legacy')->table('productos')
            ->whereNotNull('codigo')
            ->value('id');
        $product = app(CatalogRepositoryInterface::class)->find($productId);
        $this->assertNotNull($product);

        AiProductCandidate::query()->create([
            'message_id' => $message->id,
            'product_id' => $product->id,
            'product_code' => $product->code,
            'confidence' => 0.95,
            'confidence_band' => 'very_high',
            'signals' => ['exact_code' => 100.0],
            'position' => 1,
        ]);

        $this->actingAs($this->admin, 'admin')->postJson($endpoint, [
            'message_id' => $message->id,
            'rating' => 'incorrect',
            'expected_action' => 'present_product',
            'surface' => 'private',
            'severity' => 'high',
        ])->assertUnprocessable()->assertJsonValidationErrors('comment');

        $questionId = $this->firstQuestionId();
        $this->actingAs($this->admin, 'admin')->postJson($endpoint, [
            'message_id' => $message->id,
            'rating' => 'needs_adjustment',
            'expected_action' => 'present_product',
            'expected_question_id' => $questionId,
            'expected_product_id' => $product->id,
            'expected_code' => $product->code,
            'surface' => 'both',
            'severity' => 'high',
            'issue_tags' => ['wrong_question', 'ignored_data'],
            'comment' => 'Primero debería pedir el código visible de la pieza.',
        ])->assertOk();

        $review = AiTestReview::query()->sole();
        $this->assertSame($this->admin->id, $review->admin_id);
        $this->assertSame($this->family, $review->family);
        $this->assertSame('present_product', $review->expected_action);
        $this->assertSame($questionId, $review->expected_question_id);
        $this->assertSame($product->id, $review->expected_product_id);
        $this->assertSame($product->code, $review->expected_code);
        $this->assertSame('both', $review->surface);
        $this->assertSame('high', $review->severity);
        $this->assertSame('mock', $review->provider);
        $this->assertNotEmpty($review->question_bank_version);

        $this->actingAs($this->admin, 'admin')
            ->getJson("/dashboard/ia/pruebas/conversations/{$conversation->id}")
            ->assertOk()
            ->assertJsonPath('messages.0.review.rating', 'needs_adjustment')
            ->assertJsonPath('messages.0.review.expected_action', 'present_product')
            ->assertJsonPath('messages.0.review.expected_question_id', $questionId)
            ->assertJsonPath('messages.0.review.expected_product_id', $product->id)
            ->assertJsonPath('messages.0.review.expected_code', $product->code)
            ->assertJsonPath('messages.0.review.surface', 'both')
            ->assertJsonPath('messages.0.review.severity', 'high')
            ->assertJsonPath('messages.0.review.issue_tags.0', 'wrong_question');
    }

    public function test_la_referencia_esperada_debe_existir_y_ser_consistente_con_el_catalogo(): void
    {
        $conversation = $this->testConversation();
        $message = AiMessageRecord::query()->create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Encontré una pieza.',
        ]);
        $endpoint = "/dashboard/ia/pruebas/conversations/{$conversation->id}/reviews";
        $base = [
            'message_id' => $message->id,
            'rating' => 'correct',
            'expected_action' => 'present_product',
            'surface' => 'private',
            'severity' => 'medium',
        ];

        $this->actingAs($this->admin, 'admin')
            ->postJson($endpoint, [
                ...$base,
                'expected_action' => 'invent_product',
                'surface' => 'external',
                'severity' => 'urgent',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['expected_action', 'surface', 'severity']);

        $this->actingAs($this->admin, 'admin')
            ->postJson($endpoint, [...$base, 'expected_product_id' => PHP_INT_MAX])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('expected_product_id');

        $productId = (int) DB::connection('mysql_legacy')->table('productos')
            ->whereNotNull('codigo')
            ->value('id');
        $product = app(CatalogRepositoryInterface::class)->find($productId);
        $this->assertNotNull($product);

        $this->actingAs($this->admin, 'admin')
            ->postJson($endpoint, [
                ...$base,
                'expected_product_id' => $product->id,
                'expected_code' => 'CODIGO-QUE-NO-CORRESPONDE',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('expected_code');

        $this->actingAs($this->admin, 'admin')
            ->postJson($endpoint, [...$base, 'expected_code' => 'CODIGO-INEXISTENTE-XYZ'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('expected_code');
    }

    public function test_el_panel_resume_solo_las_evaluaciones_del_admin_y_calcula_una_tasa_observada(): void
    {
        $conversation = $this->testConversation();

        foreach (['correct', 'needs_adjustment', 'incorrect'] as $index => $rating) {
            $message = AiMessageRecord::query()->create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => 'Respuesta '.($index + 1),
            ]);

            AiTestReview::query()->create([
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'admin_id' => $this->admin->id,
                'family' => $this->family,
                'rating' => $rating,
                'expected_action' => 'ask_question',
                'surface' => 'private',
                'severity' => 'medium',
                'question_bank_version' => app(DiagnosticQuestionBank::class)->version(),
            ]);
        }

        $otherMessage = AiMessageRecord::query()->create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Respuesta ajena',
        ]);
        AiTestReview::query()->create([
            'conversation_id' => $conversation->id,
            'message_id' => $otherMessage->id,
            'admin_id' => 99002,
            'family' => $this->family,
            'rating' => 'correct',
            'expected_action' => 'ask_question',
            'surface' => 'private',
            'severity' => 'low',
            'question_bank_version' => app(DiagnosticQuestionBank::class)->version(),
        ]);

        $response = $this->actingAs($this->admin, 'admin')->get('/dashboard/ia/pruebas')->assertOk();
        $this->assertSame([
            'total' => 3,
            'correct' => 1,
            'needs_adjustment' => 1,
            'incorrect' => 1,
            'observed_rate' => 33.3,
        ], $response->viewData('reviewMetrics'));
        $response->assertSee('Tasa observada en estas evaluaciones')->assertSee('33,3 %');
    }

    public function test_una_derivacion_de_prueba_no_entra_como_pendiente_operativa(): void
    {
        $conversation = $this->testConversation();
        $customer = app(CustomerRepositoryInterface::class)->find($this->customer->id);
        $this->assertNotNull($customer);

        $handoff = app(HumanHandoffService::class)->open(
            $conversation,
            $customer,
            HumanHandoffService::LOW_CONFIDENCE,
        );

        $this->assertSame('test', $handoff->status);
        $this->assertSame(0, AiHandoff::query()->where('status', 'pending')->count());
    }

    public function test_los_adjuntos_de_prueba_son_privados_para_el_admin_propietario(): void
    {
        Storage::fake('ai_private');
        config(['bmh.attachments.disk' => 'ai_private']);

        $conversation = $this->testConversation();
        $file = UploadedFile::fake()->image('rotor.jpg', 600, 400);

        $response = $this->actingAs($this->admin, 'admin')
            ->post("/dashboard/ia/pruebas/conversations/{$conversation->id}/attachments", [
                'images' => [$file],
            ])->assertCreated();

        $attachmentId = (int) $response->json('attachments.0.id');
        $this->get("/dashboard/ia/pruebas/attachments/{$attachmentId}")->assertOk();

        $other = new Admin(['name' => 'Otro', 'rol' => 'administrador']);
        $other->id = 99002;
        $this->actingAs($other, 'admin')
            ->get("/dashboard/ia/pruebas/attachments/{$attachmentId}")
            ->assertNotFound();

        $this->assertSame(1, AiAttachment::query()->count());
    }

    private function testConversation(?int $testerAdminId = null): AiConversation
    {
        return AiConversation::query()->create([
            'customer_id' => $this->customer->id,
            'mode' => 'test',
            'tester_admin_id' => $testerAdminId ?? $this->admin->id,
            'test_family' => $this->family,
            'status' => 'active',
            'prompt_version' => (string) config('bmh.ai.prompt_version'),
        ]);
    }

    private function firstFamily(): string
    {
        $families = app(DiagnosticQuestionBank::class)->families();
        $key = array_key_first($families);
        $first = $families[$key];

        if (is_array($first)) {
            return (string) ($first['key'] ?? $first['slug'] ?? $first['name']);
        }

        return is_string($key) ? $key : (string) $first;
    }

    private function firstQuestionId(): string
    {
        $question = app(DiagnosticQuestionBank::class)->questionsForFamily($this->family)[0];

        return (string) $question['id'];
    }
}
