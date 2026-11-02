<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Domain\Catalog\DTO\AttributeValue;
use App\Domain\Catalog\DTO\CategoryView;
use App\Domain\Catalog\DTO\ProductCondition;
use App\Domain\Catalog\DTO\ProductView;
use App\Domain\Catalog\LegacyAttributeMap;
use App\Domain\Search\CandidateDisambiguationService;
use App\Domain\Search\DTO\Candidate;
use App\Domain\Support\Provenance;
use App\Services\Ai\DiagnosticQuestionBank;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class DiagnosticQuestionBankTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = sys_get_temp_dir().'/bmh_question_bank_'.uniqid().'.json';
        file_put_contents($this->path, json_encode($this->fixture(), JSON_THROW_ON_ERROR));
    }

    protected function tearDown(): void
    {
        File::delete($this->path);

        parent::tearDown();
    }

    public function test_expone_version_familias_y_preguntas_sin_perder_metadatos(): void
    {
        $bank = new DiagnosticQuestionBank($this->path);

        $this->assertSame('bmh-diagnostic-questions-v1', $bank->version());
        $this->assertCount(2, $bank->families());
        $this->assertArrayNotHasKey('questions', $bank->families()[0]);
        $this->assertSame('rotors', $bank->families()[0]['key']);
        $this->assertCount(2, $bank->questionsForFamily('ROTORES'));
        $this->assertTrue($bank->hasQuestion('rotors.q02'));
        $this->assertTrue($bank->hasQuestion('rotors', 'rotors.q02'));
        $this->assertFalse($bank->hasQuestion('bearings', 'rotors.q02'));
        $this->assertFalse($bank->hasQuestion('rotors.unknown'));
    }

    public function test_el_archivo_versionado_real_se_puede_consultar_por_rubro_y_atributo(): void
    {
        $bank = app(DiagnosticQuestionBank::class);

        $this->assertSame('2026-10-21', $bank->version());
        $this->assertCount(14, $bank->families());

        $question = $bank->questionForAttribute('MOTORES DE ARRANQUE', 'voltage');

        $this->assertNotNull($question);
        $this->assertSame('starter_motors.q03', $question['id']);
        $this->assertStringContainsString('12V o 24V', $question['question']);
    }

    public function test_toda_clave_filtrable_del_banco_corresponde_a_un_dato_real_del_catalogo(): void
    {
        $keys = app(DiagnosticQuestionBank::class)->filterableFactKeys();

        $this->assertCount(24, $keys);
        $this->assertContains('pins', $keys);
        $this->assertContains('inner_diameter', $keys);
        $this->assertContains('outer_diameter', $keys);

        foreach (array_diff($keys, ['brand', 'code']) as $key) {
            $this->assertNotNull(
                LegacyAttributeMap::slotForKey($key),
                "La clave {$key} del banco no existe en LegacyAttributeMap.",
            );
        }
    }

    public function test_el_banco_real_no_marca_como_automaticas_preguntas_de_codigo_que_el_desambiguador_no_puede_rankear(): void
    {
        $payload = json_decode(
            (string) file_get_contents(resource_path('data/ai-question-bank.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $questions = [];
        foreach ($payload['families'] as $family) {
            $questions = array_merge($questions, $family['questions']);
        }

        $codeQuestions = array_values(array_filter(
            $questions,
            static fn (array $question): bool => $question['fact_keys'] === ['code'],
        ));
        $automatic = array_values(array_filter(
            $questions,
            static fn (array $question): bool => $question['enabled'] === true,
        ));

        $this->assertCount(105, $questions);
        $this->assertCount(54, $automatic);
        $this->assertCount(7, $codeQuestions);

        foreach ($codeQuestions as $question) {
            $this->assertTrue($question['catalog_filterable']);
            $this->assertFalse($question['enabled'], $question['id'].' no es seleccionable por ganancia de información.');
        }
    }

    public function test_solo_devuelve_preguntas_habilitadas_y_filtrables_del_rubro(): void
    {
        $bank = new DiagnosticQuestionBank($this->path);

        $question = $bank->questionForAttribute('ROTORES', 'voltage');

        $this->assertNotNull($question);
        $this->assertSame('rotors.q02', $question['id']);
        $this->assertSame('¿El alternador donde estaba colocado es de 12V o 24V?', $question['question']);
        $this->assertSame('bmh-diagnostic-questions-v1', $question['question_bank_version']);
        $this->assertNull($bank->questionForAttribute('ROTORES', 'diameter'));
        $this->assertNull($bank->questionForAttribute('ALTERNADORES', 'voltage'));
    }

    public function test_el_desambiguador_conserva_la_ganancia_y_agrega_la_redaccion_validada(): void
    {
        config()->set('bmh.disambiguation.present_threshold', 3);
        config()->set('bmh.disambiguation.min_information_gain', 0.15);

        $service = new CandidateDisambiguationService(new DiagnosticQuestionBank($this->path));
        $candidates = [
            $this->candidate(1, '12'),
            $this->candidate(2, '24'),
            $this->candidate(3, '12'),
            $this->candidate(4, '24'),
        ];

        $next = $service->nextQuestion($candidates);

        $this->assertTrue($next['should_ask']);
        $this->assertSame('voltage', $next['attribute']['key']);
        $this->assertGreaterThan(0.15, $next['attribute']['gain']);
        $this->assertSame('rotors.q02', $next['attribute']['question_id']);
        $this->assertSame(
            '¿El alternador donde estaba colocado es de 12V o 24V?',
            $next['attribute']['question'],
        );
        $this->assertSame('bmh-diagnostic-questions-v1', $next['attribute']['question_bank_version']);
    }

    public function test_si_el_archivo_no_existe_degrada_sin_romper_la_desambiguacion(): void
    {
        $bank = new DiagnosticQuestionBank($this->path.'.missing');
        $service = new CandidateDisambiguationService($bank);

        $next = $service->nextQuestion([
            $this->candidate(1, '12'),
            $this->candidate(2, '24'),
            $this->candidate(3, '12'),
            $this->candidate(4, '24'),
        ]);

        $this->assertTrue($next['should_ask']);
        $this->assertNull($next['attribute']['question']);
        $this->assertNull($next['attribute']['question_id']);
        $this->assertSame('unavailable', $next['attribute']['question_bank_version']);
    }

    private function candidate(int $id, string $voltage): Candidate
    {
        $category = new CategoryView(41, 'ROTORES', null, [1], 4);
        $attribute = new AttributeValue(
            key: 'voltage',
            label: 'Voltaje',
            value: $voltage,
            type: 'electrical',
            unit: 'V',
            provenance: Provenance::database('productos', $id),
        );

        return new Candidate(new ProductView(
            id: $id,
            code: 'TEST-'.$id,
            name: 'Rotor '.$id,
            category: $category,
            brand: null,
            model: null,
            condition: ProductCondition::New,
            attributes: [$attribute],
            equivalences: [],
            relatedParts: [],
            applications: [],
            images: [],
            listPrice: null,
            hasDuplicateCode: false,
            searchableText: 'rotor '.$voltage.'v',
        ));
    }

    /** @return array<string, mixed> */
    private function fixture(): array
    {
        return [
            'version' => 'bmh-diagnostic-questions-v1',
            'families' => [
                [
                    'id' => 'rotors',
                    'source_group' => 'ROTORES',
                    'category_id' => 41,
                    'category_name' => 'ROTORES',
                    'label' => 'Rotores',
                    'questions' => [
                        [
                            'id' => 'rotors.q01',
                            'priority' => 1,
                            'question' => 'Pregunta deshabilitada.',
                            'fact_keys' => ['diameter'],
                            'kind' => 'attribute',
                            'answer_type' => 'measurement',
                            'unit' => 'mm',
                            'catalog_filterable' => true,
                            'enabled' => false,
                        ],
                        [
                            'id' => 'rotors.q02',
                            'priority' => 2,
                            'question' => '¿El alternador donde estaba colocado es de 12V o 24V?',
                            'fact_keys' => ['voltage'],
                            'kind' => 'attribute',
                            'answer_type' => 'choice',
                            'unit' => 'V',
                            'catalog_filterable' => true,
                            'enabled' => true,
                        ],
                    ],
                ],
                [
                    'id' => 'bearings',
                    'source_group' => 'RODAMIENTOS',
                    'category_id' => 44,
                    'category_name' => 'RODAMIENTOS',
                    'label' => 'Rodamientos',
                    'questions' => [],
                ],
            ],
        ];
    }
}
