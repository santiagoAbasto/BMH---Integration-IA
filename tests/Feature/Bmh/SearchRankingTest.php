<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Domain\Catalog\Contracts\CatalogRepositoryInterface;
use App\Domain\Catalog\DuplicateProductResolver;
use App\Domain\Search\CandidateDisambiguationService;
use App\Domain\Search\CandidateRankingService;
use App\Domain\Search\DTO\SearchQuery;
use App\Domain\Search\HybridProductSearchService;
use App\Domain\Search\QueryRouter;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SearchRankingTest extends TestCase
{
    private function search(): HybridProductSearchService
    {
        return app(HybridProductSearchService::class);
    }

    public function test_el_codigo_exacto_le_gana_a_cualquier_similitud_visual(): void
    {
        // Regla dura del proyecto. Se verifica sobre el peso configurado y sobre
        // el resultado real del ranking.
        $catalog = app(CatalogRepositoryInterface::class);
        $target  = $catalog->findByCode('1833')[0] ?? null;

        $this->assertNotNull($target);

        // Un pool donde el producto correcto entra último y los otros traen
        // señales blandas: misma marca, mismo rubro, y "parecido visual".
        $others = $catalog->searchByKeywords('rotor', [$target->category?->id ?? 41], 8);

        $pool = [...$others, $target];

        $ranked = app(CandidateRankingService::class)->rank($pool, new SearchQuery(
            rawText: 'rotor bosch parecido',
            code: '1833',
            brand: 'BOSCH',
            fromVision: true,
        ));

        $this->assertSame(
            $target->id,
            $ranked[0]->product->id,
            'El match exacto de código tiene que quedar primero aunque los otros tengan marca y visión a favor.',
        );
        $this->assertArrayHasKey('exact_code', $ranked[0]->signals);
    }

    public function test_el_peso_de_vision_esta_muy_por_debajo_del_de_codigo(): void
    {
        $weights = config('bmh.ranking.weights');

        $this->assertGreaterThan(
            $weights['vision_similarity'] * 10,
            $weights['exact_code'],
            'La similitud visual no puede acercarse al código exacto ni sumando varias señales.',
        );
    }

    public function test_un_codigo_exacto_alcanza_confianza_muy_alta(): void
    {
        $candidates = $this->search()->search(new SearchQuery(code: '1833'));

        $this->assertNotEmpty($candidates);
        $this->assertSame('very_high', $candidates[0]->confidenceBand());
        $this->assertSame('1833', $candidates[0]->product->code);
    }

    public function test_una_busqueda_por_texto_sin_codigo_nunca_llega_a_muy_alta(): void
    {
        // Sin código no se puede afirmar una pieza: el techo de confianza es
        // deliberadamente bajo.
        $candidates = $this->search()->search(new SearchQuery(rawText: 'rotor bosch', brand: 'BOSCH'));

        $this->assertNotEmpty($candidates);

        foreach ($candidates as $candidate) {
            $this->assertLessThan(0.75, $candidate->confidence());
        }
    }

    public function test_no_devuelve_productos_ocultos(): void
    {
        $hidden = DB::connection('mysql_legacy')->table('productos')->where('estado', 0)->first();

        if ($hidden === null) {
            $this->markTestSkipped('No hay productos con estado 0.');
        }

        $candidates = $this->search()->search(new SearchQuery(code: (string) $hidden->codigo));

        $returnedIds = array_map(
            static fn ($candidate): int => $candidate->product->id,
            $candidates,
        );

        $this->assertNotContains(
            (int) $hidden->id,
            $returnedIds,
            'Un producto con estado 0 no se le ofrece al cliente.',
        );
    }

    public function test_el_router_manda_los_codigos_a_busqueda_deterministica(): void
    {
        $router = app(QueryRouter::class);

        // No hace falta gastar una llamada al modelo para entender "REG40016".
        $this->assertTrue($router->looksLikeCode('REG40016'));
        $this->assertTrue($router->looksLikeCode('912345'));
        $this->assertTrue($router->looksLikeCode('RP 033155'));
        $this->assertFalse($router->looksLikeCode('necesito un rotor'));
        $this->assertFalse($router->looksLikeCode('rotor'));

        $this->assertSame(QueryRouter::EXACT, $router->route(new SearchQuery(code: 'REG40016')));
        $this->assertSame(QueryRouter::HYBRID, $router->route(new SearchQuery(rawText: 'un rotor'), true));
    }

    public function test_extrae_un_codigo_embebido_en_una_frase(): void
    {
        $router = app(QueryRouter::class);

        $this->assertSame('912345', $router->extractCode('tenés el 912345?'));
        $this->assertSame('REG40016', $router->extractCode('busco el REG40016 para un Toyota'));
        $this->assertNull($router->extractCode('necesito un rotor chico'));
    }

    public function test_filtra_por_atributos_tecnicos_reales(): void
    {
        $candidates = $this->search()->search(new SearchQuery(
            categoryIds: [41], // ROTORES
            attributes: ['voltage' => '24v'],
        ));

        $this->assertNotEmpty($candidates, 'Debería haber rotores de 24v en el catálogo.');

        foreach (array_slice($candidates, 0, 5) as $candidate) {
            $voltage = $candidate->product->attribute('voltage');

            if ($voltage !== null) {
                $this->assertStringContainsStringIgnoringCase('24', $voltage->value);
            }
        }
    }

    public function test_ante_un_codigo_duplicado_no_elige_el_primero(): void
    {
        $catalog  = app(CatalogRepositoryInterface::class);
        $resolver = app(DuplicateProductResolver::class);

        $duplicates = $catalog->duplicateCodes();
        $code       = (string) array_key_first($duplicates);

        $resolution = $resolver->resolve($catalog->findByCode($code));

        // Si ambos son publicables, el sistema tiene que pedir un dato, no elegir.
        if ($resolution['ambiguous']) {
            $this->assertNull($resolution['resolved']);
            $this->assertNotEmpty(
                $resolution['distinguishing'],
                'Si es ambiguo, tiene que decir por qué atributo distinguirlos.',
            );
        } else {
            $this->assertNotNull($resolution['resolved']);
        }
    }

    public function test_los_atributos_discriminantes_son_los_que_difieren(): void
    {
        $catalog  = app(CatalogRepositoryInterface::class);
        $resolver = app(DuplicateProductResolver::class);

        $products = $catalog->searchByKeywords('rotor', [41], 4);

        $this->assertGreaterThanOrEqual(2, count($products));

        foreach ($resolver->distinguishingAttributes($products) as $attribute) {
            $values = array_values($attribute['values']);

            $this->assertGreaterThan(
                1,
                count(array_unique($values)),
                "El atributo {$attribute['key']} no distingue: todos tienen el mismo valor.",
            );
        }
    }

    public function test_la_pregunta_mas_util_es_la_que_mas_reduce(): void
    {
        $candidates = $this->search()->search(new SearchQuery(rawText: 'rotor', categoryIds: [41]));

        $this->assertGreaterThan(3, count($candidates));

        $next = app(CandidateDisambiguationService::class)->nextQuestion($candidates);

        $this->assertTrue($next['should_ask']);
        $this->assertNotNull($next['attribute']);
        $this->assertGreaterThan(
            (float) config('bmh.disambiguation.min_information_gain'),
            $next['attribute']['gain'],
        );

        // Las alternativas tienen que venir ordenadas por ganancia decreciente.
        $gains = array_map(static fn (array $a): float => $a['gain'], $next['alternatives']);
        $sorted = $gains;
        rsort($sorted);
        $this->assertSame($sorted, $gains);
    }

    public function test_no_pregunta_cuando_hay_pocos_candidatos(): void
    {
        $candidates = $this->search()->search(new SearchQuery(code: '1833'));

        $next = app(CandidateDisambiguationService::class)->nextQuestion($candidates);

        $this->assertFalse($next['should_ask']);
        $this->assertContains($next['reason'], ['few_enough_to_present', 'high_confidence_match']);
    }

    public function test_no_pregunta_por_un_atributo_que_no_parte_el_conjunto(): void
    {
        $catalog = app(CatalogRepositoryInterface::class);

        // Todos los candidatos con el mismo voltaje: preguntar por voltaje no
        // reduce nada, así que ese atributo no puede ganar.
        $products = $catalog->searchByAttributes(['voltage' => '12v'], [41], 12);

        if (count($products) < 4) {
            $this->markTestSkipped('No hay suficientes rotores de 12v.');
        }

        $candidates = app(CandidateRankingService::class)->rank($products, new SearchQuery(categoryIds: [41]));
        $ranked     = app(CandidateDisambiguationService::class)->rankAttributesByGain($candidates);

        foreach ($ranked as $attribute) {
            if ($attribute['key'] === 'voltage') {
                $this->fail('Voltaje no debería aparecer: todos los candidatos comparten el mismo valor.');
            }
        }

        $this->assertTrue(true);
    }

    public function test_nunca_pregunta_por_atributos_de_la_lista_negra(): void
    {
        $candidates = $this->search()->search(new SearchQuery(rawText: 'rotor', categoryIds: [41]));
        $ranked     = app(CandidateDisambiguationService::class)->rankAttributesByGain($candidates);

        $blacklist = array_map('mb_strtoupper', (array) config('bmh.disambiguation.never_ask'));

        foreach ($ranked as $attribute) {
            $this->assertNotContains(mb_strtoupper($attribute['label']), $blacklist);
        }
    }

    public function test_el_historial_del_cliente_suma_pero_no_domina(): void
    {
        $catalog  = app(CatalogRepositoryInterface::class);
        $products = $catalog->searchByKeywords('rotor', [41], 6);

        $this->assertGreaterThanOrEqual(2, count($products));

        $lastId = $products[count($products) - 1]->id;

        // El último producto entra como "ya comprado" pero sin código: no puede
        // ganarle a un match exacto de código.
        $ranked = app(CandidateRankingService::class)->rank($products, new SearchQuery(
            code: $products[0]->code,
            customerProductIds: [$lastId],
        ));

        $this->assertSame($products[0]->id, $ranked[0]->product->id);
    }

    public function test_una_busqueda_vacia_no_devuelve_todo_el_catalogo(): void
    {
        $this->assertSame([], $this->search()->search(new SearchQuery()));
    }

    public function test_la_busqueda_por_codigo_responde_rapido(): void
    {
        // Con 5.054 productos, una búsqueda bien armada tiene que sentirse
        // inmediata. Se calienta el cache primero para medir la consulta.
        $this->search()->search(new SearchQuery(code: '1833'));

        $started = microtime(true);
        $this->search()->search(new SearchQuery(code: 'REG40016'));
        $elapsed = (microtime(true) - $started) * 1000;

        $this->assertLessThan(400, $elapsed, "La búsqueda por código tardó {$elapsed} ms.");
    }
}
