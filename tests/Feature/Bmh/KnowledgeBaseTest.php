<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Domain\Catalog\Contracts\CatalogRepositoryInterface;
use App\Models\Admin;
use App\Models\Ai\AiKnowledge;
use App\Services\Ai\KnowledgeBaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Base de conocimiento: lo que el equipo enseña (términos, reglas, notas) viaja
 * al prompt del modelo, pero sólo si está aprobado y activo. Una persona aprueba
 * lo que afecta a todos; se desactiva al instante.
 */
final class KnowledgeBaseTest extends TestCase
{
    private Admin $admin;

    private int $categoryId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--path' => 'database/migrations/2026_11_19_000001_create_ai_knowledge_table.php', '--force' => true])
            ->assertSuccessful();
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_11_24_000001_add_reference_paths_to_ai_knowledge.php', '--force' => true])
            ->assertSuccessful();

        DB::connection('mysql_ai')->table('ai_knowledge')->delete();
        Storage::fake('ai_private');

        $this->admin = new Admin(['name' => 'Tester IA', 'rol' => 'administrador']);
        $this->admin->id = 99002;

        $this->categoryId = (int) app(CatalogRepositoryInterface::class)->categories()[0]->id;
    }

    protected function tearDown(): void
    {
        DB::connection('mysql_ai')->table('ai_knowledge')->delete();

        parent::tearDown();
    }

    private function entry(array $overrides = []): AiKnowledge
    {
        return AiKnowledge::query()->create(array_merge([
            'kind' => AiKnowledge::KIND_TERM,
            'title' => 'arr.',
            'body' => '«arr.» es lo mismo que inducido, bobina o bobina de arranque.',
            'status' => AiKnowledge::STATUS_APPROVED,
            'source' => 'admin',
            'active' => true,
        ], $overrides));
    }

    public function test_sin_entradas_el_bloque_viene_vacio(): void
    {
        $this->assertSame('', app(KnowledgeBaseService::class)->promptSection());
    }

    public function test_solo_las_aprobadas_y_activas_viajan_al_prompt(): void
    {
        $this->entry(); // aprobada + activa
        $this->entry(['title' => 'pendiente', 'body' => 'todavía no revisada', 'status' => AiKnowledge::STATUS_PENDING]);
        $this->entry(['title' => 'apagada', 'body' => 'desactivada a mano', 'active' => false]);

        $section = app(KnowledgeBaseService::class)->promptSection();

        $this->assertStringContainsString('Glosario y reglas del negocio', $section);
        $this->assertStringContainsString('bobina de arranque', $section);
        $this->assertStringNotContainsString('todavía no revisada', $section);
        $this->assertStringNotContainsString('desactivada a mano', $section);
    }

    public function test_el_filtro_por_rubro_incluye_las_globales_y_las_del_rubro(): void
    {
        $this->entry(['title' => 'global', 'body' => 'vale para todo']);
        $this->entry(['title' => 'del rubro', 'body' => 'vale para este rubro', 'category_id' => $this->categoryId]);
        $this->entry(['title' => 'otro rubro', 'body' => 'vale para otro', 'category_id' => $this->categoryId + 100000]);

        $section = app(KnowledgeBaseService::class)->promptSection([$this->categoryId]);

        $this->assertStringContainsString('vale para todo', $section);
        $this->assertStringContainsString('vale para este rubro', $section);
        $this->assertStringNotContainsString('vale para otro', $section);
    }

    public function test_un_admin_agrega_conocimiento_y_el_modelo_lo_ve(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post('/dashboard/ia/aprendizaje/conocimiento', [
                'kind' => 'term',
                'title' => 'alt.',
                'body' => '«alt.» es alternador.',
            ])
            ->assertRedirect();

        $row = AiKnowledge::query()->firstOrFail();
        $this->assertSame(AiKnowledge::STATUS_APPROVED, $row->status);
        $this->assertTrue($row->active);
        $this->assertSame('admin', $row->source);
        $this->assertStringContainsString('alternador', app(KnowledgeBaseService::class)->promptSection());
    }

    public function test_lo_pendiente_no_se_usa_hasta_que_un_admin_lo_aprueba(): void
    {
        $pendiente = $this->entry([
            'title' => 'equivalencia de un demo',
            'body' => 'esto lo enseñó una cuenta demo',
            'status' => AiKnowledge::STATUS_PENDING,
            'source' => 'demo_chat',
        ]);

        $this->assertStringNotContainsString('esto lo enseñó una cuenta demo', app(KnowledgeBaseService::class)->promptSection());

        $this->actingAs($this->admin, 'admin')
            ->post("/dashboard/ia/aprendizaje/conocimiento/{$pendiente->id}/aprobar")
            ->assertRedirect();

        $this->assertSame(AiKnowledge::STATUS_APPROVED, $pendiente->refresh()->status);
        $this->assertStringContainsString('esto lo enseñó una cuenta demo', app(KnowledgeBaseService::class)->promptSection());

        // Desactivar la saca del prompt al instante.
        $this->actingAs($this->admin, 'admin')
            ->post("/dashboard/ia/aprendizaje/conocimiento/{$pendiente->id}/alternar")
            ->assertRedirect();

        $this->assertStringNotContainsString('esto lo enseñó una cuenta demo', app(KnowledgeBaseService::class)->promptSection());
    }
}
