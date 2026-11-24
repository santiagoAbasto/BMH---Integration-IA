<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Domain\Search\DTO\SearchQuery;
use App\Domain\Search\HybridProductSearchService;
use App\Domain\Search\KnowledgeSynonymsService;
use App\Models\Ai\AiKnowledge;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Fase 3: los sinónimos enseñados afinan la BÚSQUEDA, no sólo lo que lee el modelo.
 *
 *  - «arr» se expande con «inducido / bobina de arranque» antes de buscar;
 *  - una palabra que no es un término enseñado no se toca (y «arranque» no
 *    dispara «arr», que se matchea como palabra completa);
 *  - con el sinónimo enseñado la búsqueda encuentra los productos reales; al
 *    desactivar la regla, deja de hacerlo al instante.
 */
final class KnowledgeSynonymsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--path' => 'database/migrations/2026_11_19_000001_create_ai_knowledge_table.php', '--force' => true])
            ->assertSuccessful();
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_11_24_000001_add_reference_paths_to_ai_knowledge.php', '--force' => true])
            ->assertSuccessful();

        DB::connection('mysql_ai')->table('ai_knowledge')->delete();
    }

    protected function tearDown(): void
    {
        DB::connection('mysql_ai')->table('ai_knowledge')->delete();

        parent::tearDown();
    }

    private function term(string $title, string $synonyms): AiKnowledge
    {
        return AiKnowledge::query()->create([
            'kind' => AiKnowledge::KIND_TERM,
            'title' => $title,
            'body' => $title,
            'synonyms' => $synonyms,
            'status' => AiKnowledge::STATUS_APPROVED,
            'source' => 'admin',
            'active' => true,
        ]);
    }

    public function test_devuelve_los_sinonimos_del_termino_mencionado(): void
    {
        $this->term('arr.', 'inducido, bobina de arranque');

        $out = app(KnowledgeSynonymsService::class)->expansions('necesito un arr para un gol');

        $this->assertContains('inducido', $out);
        $this->assertContains('bobina de arranque', $out);
    }

    public function test_no_devuelve_nada_sin_terminos_ni_hace_falsos_positivos(): void
    {
        $this->term('arr.', 'inducido, bobina de arranque');
        $svc = app(KnowledgeSynonymsService::class);

        // No hay término enseñado en la frase.
        $this->assertSame([], $svc->expansions('necesito un alternador'));
        // «arranque» no dispara «arr»: se busca la palabra completa.
        $this->assertSame([], $svc->expansions('motor de arranque'));
    }

    public function test_la_busqueda_encuentra_productos_por_un_sinonimo_ensenado(): void
    {
        // Término que por sí solo no matchea nada, con un sinónimo real del catálogo.
        $rule = $this->term('zzsinonimotest', 'inducido');

        $search = app(HybridProductSearchService::class);

        $conRegla = $search->search(new SearchQuery(rawText: 'zzsinonimotest'));
        $this->assertNotEmpty($conRegla, 'con el sinónimo enseñado, la búsqueda encuentra inducidos');

        $rule->update(['active' => false]);

        $sinRegla = $search->search(new SearchQuery(rawText: 'zzsinonimotest'));
        $this->assertEmpty($sinRegla, 'sin la regla, el término no matchea nada');
    }
}
