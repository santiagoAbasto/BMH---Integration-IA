<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Domain\Catalog\Contracts\CatalogRepositoryInterface;
use App\Models\Admin;
use App\Models\Ai\AiAttachment;
use App\Models\Ai\AiConversation;
use App\Models\Ai\AiCorrectionRule;
use App\Models\Ai\AiDemoAccount;
use App\Models\Ai\AiFeedback;
use App\Models\Ai\AiMessageRecord;
use App\Models\User;
use App\Services\Ai\ApprovedCorrectionService;
use App\Services\Ai\AssistantAccess;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * El asesor aprende de personas: enseñanza del admin, cuentas demo y feedback.
 *
 * Lo que se fija:
 *  - la misma imagen reenviada o recomprimida se reconoce; otra imagen no;
 *  - una cuenta demo aprende al instante, un cliente común espera revisión;
 *  - sólo un administrador entra al módulo y lo aprendido se puede apagar.
 */
final class AiLearningTest extends TestCase
{
    private Admin $admin;

    private User $customer;

    private int $productId;

    private string $productCode;

    /** @var list<string> */
    private array $tmp = [];

    private ?int $logoFixtureId = null;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            '2026_11_04_000001_add_correction_workflow_to_ai_feedback',
            '2026_11_05_000001_create_ai_correction_rules_table',
            '2026_11_16_000001_add_learning_to_ai_corrections_and_demo_accounts',
        ] as $migration) {
            $this->artisan('migrate', ['--path' => "database/migrations/{$migration}.php", '--force' => true])->assertSuccessful();
        }

        foreach (['ai_demo_accounts', 'ai_correction_rules', 'ai_feedback', 'ai_product_candidates', 'ai_attachments',
            'ai_messages', 'ai_conversations'] as $table) {
            DB::connection('mysql_ai')->table($table)->delete();
        }

        Storage::fake('ai_private');
        config(['bmh.assistant_access.mode' => 'allowlist', 'bmh.assistant_access.customer_ids' => []]);

        $row = DB::connection('mysql_legacy')->table('users')->where('habilitado', 1)->where('rol', 'cliente')->first();
        $this->assertNotNull($row);
        $this->customer = (new User())->setConnection('mysql_legacy')->newQuery()->findOrFail($row->id);

        $this->admin = new Admin(['name' => 'Tester IA', 'rol' => 'administrador']);
        $this->admin->id = 99001;

        // El layout del panel exige una fila de logo; sin ella la vista no abre.
        $logo = DB::table('imagenes')->where('sector', 'logo2')->first();

        if ($logo === null) {
            $this->logoFixtureId = (int) DB::table('imagenes')->insertGetId(['path' => 'logo2-test.png', 'sector' => 'logo2']);
        } elseif ($logo->path === 'logo2-test.png') {
            $this->logoFixtureId = (int) $logo->id;
        }

        $product = app(CatalogRepositoryInterface::class)->findByCode('1833')[0];
        $this->productId = $product->id;
        $this->productCode = (string) $product->code;
    }

    protected function tearDown(): void
    {
        foreach ($this->tmp as $file) {
            @unlink($file);
        }

        if ($this->logoFixtureId !== null) {
            DB::table('imagenes')->where('id', $this->logoFixtureId)->delete();
        }

        // Una cuenta demo o una regla que sobrevive a la prueba cambia el
        // comportamiento de las demás (acceso al asesor, revisión de feedback).
        foreach (['ai_demo_accounts', 'ai_correction_rules', 'ai_feedback', 'ai_product_candidates', 'ai_attachments',
            'ai_messages', 'ai_conversations'] as $table) {
            DB::connection('mysql_ai')->table($table)->delete();
        }

        parent::tearDown();
    }

    /** Una imagen con estructura (una de cuadro y círculo, otra distinta). */
    private function image(string $kind): string
    {
        $im = imagecreatetruecolor(400, 300);
        imagefill($im, 0, 0, imagecolorallocate($im, 245, 245, 245));
        $dark = imagecolorallocate($im, 20, 40, 90);

        if ($kind === 'a') {
            imagefilledrectangle($im, 40, 40, 200, 160, $dark);
            imagefilledellipse($im, 300, 200, 120, 120, $dark);
        } else {
            imagefilledrectangle($im, 220, 20, 380, 100, $dark);
            imagefilledellipse($im, 100, 220, 140, 140, $dark);
            imagefilledrectangle($im, 0, 140, 400, 160, $dark);
        }

        $path = tempnam(sys_get_temp_dir(), 'ail').'.png';
        imagepng($im, $path);

        return $this->tmp[] = $path;
    }

    /** La misma imagen, reenviada: más chica y recomprimida. */
    private function forwarded(string $png): string
    {
        $src = imagecreatefrompng($png);
        $dst = imagescale($src, 320);
        $path = tempnam(sys_get_temp_dir(), 'ail').'.jpg';
        imagejpeg($dst, $path, 55);

        return $this->tmp[] = $path;
    }

    private function attachmentFor(string $file): AiAttachment
    {
        $conversation = AiConversation::query()->create([
            'customer_id' => $this->customer->id, 'mode' => 'customer', 'status' => 'active',
        ]);
        $stored = 'conversations/'.$conversation->id.'/'.basename($file);
        Storage::disk('ai_private')->put($stored, (string) file_get_contents($file));

        return AiAttachment::query()->create([
            'conversation_id' => $conversation->id, 'disk' => 'ai_private', 'path' => $stored,
            'mime' => 'image/jpeg', 'bytes' => filesize($file),
        ]);
    }

    private function teach(string $file, string $code): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin, 'admin')->post('/dashboard/ia/aprendizaje/ensenar', [
            'image' => new UploadedFile($file, basename($file), 'image/png', null, true),
            'code' => $code,
        ]);
    }

    public function test_la_misma_imagen_reenviada_se_reconoce_y_otra_imagen_no(): void
    {
        $original = $this->image('a');

        $this->teach($original, $this->productCode)->assertRedirect()->assertSessionHas('status');

        $rule = AiCorrectionRule::query()->sole();
        $this->assertSame('admin_teach', $rule->source);
        $this->assertSame($this->productId, $rule->expected_product_id);
        $this->assertNotNull($rule->average_hash);
        Storage::disk('ai_private')->assertExists($rule->reference_path);

        $service = app(ApprovedCorrectionService::class);

        // Bytes distintos, mismo contenido: WhatsApp, captura recomprimida.
        $reenviada = $service->apply('', [$this->attachmentFor($this->forwarded($original))], []);
        $this->assertTrue($reenviada['applied']);
        $this->assertSame($this->productId, $reenviada['candidates'][0]->product->id);

        // Otra imagen no se confunde con la enseñada.
        $otra = $service->apply('', [$this->attachmentFor($this->image('b'))], []);
        $this->assertFalse($otra['applied']);

        $this->assertSame(1, $rule->fresh()->hits);
    }

    public function test_una_regla_desactivada_deja_de_aplicarse(): void
    {
        $original = $this->image('a');
        $this->teach($original, $this->productCode);
        $rule = AiCorrectionRule::query()->sole();

        $this->actingAs($this->admin, 'admin')
            ->post("/dashboard/ia/aprendizaje/reglas/{$rule->id}/alternar")->assertRedirect();

        $this->assertFalse($rule->fresh()->active);
        $this->assertFalse(app(ApprovedCorrectionService::class)->apply('', [$this->attachmentFor($original)], [])['applied']);

        $this->actingAs($this->admin, 'admin')->delete("/dashboard/ia/aprendizaje/reglas/{$rule->id}")->assertRedirect();
        $this->assertSame(0, AiCorrectionRule::query()->count());
    }

    public function test_no_se_ensena_un_codigo_que_no_existe_en_el_catalogo(): void
    {
        $this->teach($this->image('a'), 'NO-EXISTE-9999')->assertSessionHasErrors('code');

        $this->assertSame(0, AiCorrectionRule::query()->count());
    }

    /** @return array{0:int,1:int} [conversación, mensaje de la respuesta evaluada] */
    private function answeredConversation(User $customer, string $question): array
    {
        $conversation = AiConversation::query()->create([
            'customer_id' => $customer->id, 'mode' => 'customer', 'status' => 'active',
        ]);
        AiMessageRecord::query()->create([
            'conversation_id' => $conversation->id, 'role' => 'user', 'content' => $question,
        ]);
        $answer = AiMessageRecord::query()->create([
            'conversation_id' => $conversation->id, 'role' => 'assistant', 'content' => 'No lo encontré.',
        ]);

        return [$conversation->id, $answer->id];
    }

    public function test_una_cuenta_demo_aprende_al_instante_y_un_cliente_comun_espera_revision(): void
    {
        config(['bmh.assistant_access.mode' => 'all']);
        [$conversationId, $messageId] = $this->answeredConversation($this->customer, 'Necesito el rotor de la foto 1');

        $payload = [
            'message_id' => $messageId, 'was_correct' => false,
            'reason' => 'Era otro artículo', 'expected_code' => $this->productCode,
        ];

        // Cliente común: queda pendiente, no se aprende nada.
        $this->actingAs($this->customer)->postJson("/api/assistant/conversations/{$conversationId}/feedback", $payload)->assertOk();
        $this->assertSame('pending', AiFeedback::query()->sole()->review_status);
        $this->assertSame(0, AiCorrectionRule::query()->count());

        // Cuenta demo: el mismo feedback se aprende sin esperar a nadie.
        AiDemoAccount::query()->create(['customer_id' => $this->customer->id, 'active' => true]);
        [$conversationId, $messageId] = $this->answeredConversation($this->customer, 'Necesito el rotor de la foto 2');
        $this->actingAs($this->customer)->postJson("/api/assistant/conversations/{$conversationId}/feedback", [
            ...$payload, 'message_id' => $messageId,
        ])->assertOk();

        $rule = AiCorrectionRule::query()->sole();
        $this->assertSame('demo_account', $rule->source);
        $this->assertSame($this->productId, $rule->expected_product_id);
        $this->assertSame('approved', AiFeedback::query()->latest('id')->first()->review_status);

        $applied = app(ApprovedCorrectionService::class)->apply('Necesito el rotor de la foto 2', [], []);
        $this->assertTrue($applied['applied']);
    }

    public function test_el_like_de_una_cuenta_demo_refuerza_el_producto_confirmado(): void
    {
        config(['bmh.assistant_access.mode' => 'all']);
        AiDemoAccount::query()->create(['customer_id' => $this->customer->id, 'active' => true]);
        [$conversationId, $messageId] = $this->answeredConversation($this->customer, 'rotor 1833');
        $shown = AiMessageRecord::query()->findOrFail($messageId);
        $shown->candidates()->create(['product_id' => $this->productId, 'product_code' => $this->productCode,
            'confidence' => 0.9, 'confidence_band' => 'high', 'signals' => [], 'position' => 0]);

        $this->actingAs($this->customer)->postJson("/api/assistant/conversations/{$conversationId}/feedback", [
            'message_id' => $messageId, 'product_id' => $this->productId, 'was_correct' => true,
        ])->assertOk();

        $this->assertSame($this->productId, AiCorrectionRule::query()->sole()->expected_product_id);
    }

    public function test_las_cuentas_demo_activas_acceden_al_asesor_sin_tocar_el_env(): void
    {
        $this->assertFalse(AssistantAccess::permite($this->customer));

        $demo = AiDemoAccount::query()->create(['customer_id' => $this->customer->id, 'active' => true]);
        $this->assertTrue(AssistantAccess::permite($this->customer));

        $demo->update(['active' => false]);
        $this->assertFalse(AssistantAccess::permite($this->customer));
    }

    public function test_solo_un_admin_entra_al_modulo_y_gestiona_cuentas_demo(): void
    {
        $this->get('/dashboard/ia/aprendizaje')->assertRedirect('/login/admin');
        $this->actingAs($this->customer, 'web')->get('/dashboard/ia/aprendizaje')->assertRedirect('/login/admin');

        $this->actingAs($this->admin, 'admin')->get('/dashboard/ia/aprendizaje')
            ->assertOk()->assertSee('Aprendizaje de la IA')->assertSee('Enseñar al asesor');

        $this->actingAs($this->admin, 'admin')->post('/dashboard/ia/aprendizaje/cuentas-demo', [
            'customer' => (string) $this->customer->id, 'label' => 'Taller de prueba',
        ])->assertRedirect()->assertSessionHas('status');

        $demo = AiDemoAccount::query()->sole();
        $this->assertTrue($demo->active);
        $this->assertSame($this->customer->id, $demo->customer_id);

        $this->actingAs($this->admin, 'admin')->get('/dashboard/ia/aprendizaje')
            ->assertOk()->assertSee('Taller de prueba');

        $this->actingAs($this->admin, 'admin')->post('/dashboard/ia/aprendizaje/cuentas-demo', ['customer' => '99999999'])
            ->assertSessionHasErrors('customer');
    }
}
