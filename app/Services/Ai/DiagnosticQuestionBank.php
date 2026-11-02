<?php

declare(strict_types=1);

namespace App\Services\Ai;

/**
 * Banco versionado de preguntas de diagnóstico aportadas por BMH.
 *
 * El archivo es conocimiento de negocio, no un prompt: este servicio sólo
 * devuelve preguntas habilitadas y vinculadas a datos que el catálogo puede
 * usar. Si el archivo falta o está dañado, el desambiguador conserva su
 * comportamiento anterior basado en la etiqueta del atributo.
 */
final class DiagnosticQuestionBank
{
    /** @var array{version?:mixed,families?:mixed}|null */
    private ?array $data = null;

    public function __construct(
        private readonly ?string $path = null,
    ) {
    }

    public function version(): string
    {
        $version = $this->load()['version'] ?? null;

        return is_string($version) && trim($version) !== ''
            ? trim($version)
            : 'unavailable';
    }

    /**
     * Metadatos de las familias, sin duplicar sus preguntas.
     *
     * @return list<array<string, mixed>>
     */
    public function families(): array
    {
        return array_map(
            static function (array $family): array {
                unset($family['questions']);

                if (is_string($family['id'] ?? null)) {
                    $family['key'] = $family['id'];
                }

                return $family;
            },
            $this->familyRows(),
        );
    }

    /** @return list<array<string, mixed>> */
    public function questionsForFamily(string $family): array
    {
        $needle = $this->normalize($family);

        foreach ($this->familyRows() as $row) {
            if (! $this->familyMatches($row, $needle)) {
                continue;
            }

            $questions = array_values(array_filter(
                is_array($row['questions'] ?? null) ? $row['questions'] : [],
                static fn (mixed $question): bool => is_array($question),
            ));

            usort($questions, static function (array $left, array $right): int {
                return ((int) ($left['priority'] ?? PHP_INT_MAX)) <=> ((int) ($right['priority'] ?? PHP_INT_MAX));
            });

            return $questions;
        }

        return [];
    }

    public function hasQuestion(string $familyOrQuestionId, ?string $questionId = null): bool
    {
        $questions = $questionId === null
            ? array_merge(...array_map(
                static fn (array $family): array => (array) ($family['questions'] ?? []),
                $this->familyRows(),
            ))
            : $this->questionsForFamily($familyOrQuestionId);

        $expectedId = $questionId ?? $familyOrQuestionId;

        foreach ($questions as $question) {
            if (is_array($question) && ($question['id'] ?? null) === $expectedId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Claves que una pregunta activa puede convertir en un filtro real.
     *
     * Esta lista alimenta tanto el schema de extracción como la memoria de la
     * conversación. Así una pregunta nueva no queda visible en el panel si la
     * respuesta después se pierde entre turnos.
     *
     * @return list<string>
     */
    public function filterableFactKeys(): array
    {
        $keys = [];

        foreach ($this->familyRows() as $family) {
            foreach ((array) ($family['questions'] ?? []) as $question) {
                if (! is_array($question)
                    || ($question['enabled'] ?? false) !== true
                    || ($question['catalog_filterable'] ?? false) !== true) {
                    continue;
                }

                foreach ((array) ($question['fact_keys'] ?? []) as $key) {
                    if (is_string($key) && trim($key) !== '') {
                        $keys[trim($key)] = true;
                    }
                }
            }
        }

        $keys = array_keys($keys);
        sort($keys);

        return $keys;
    }

    /**
     * Devuelve la redacción prioritaria para una clave canónica y un rubro.
     * Sólo considera preguntas explícitamente habilitadas y filtrables.
     *
     * @return array<string, mixed>|null
     */
    public function questionForAttribute(?string $categoryName, string $key): ?array
    {
        if ($categoryName === null || trim($categoryName) === '' || trim($key) === '') {
            return null;
        }

        $familyNeedle = $this->normalize($categoryName);
        $matches = [];

        foreach ($this->familyRows() as $family) {
            if (! $this->familyMatches($family, $familyNeedle)) {
                continue;
            }

            foreach ((array) ($family['questions'] ?? []) as $question) {
                if (! is_array($question)
                    || ($question['enabled'] ?? false) !== true
                    || ($question['catalog_filterable'] ?? false) !== true) {
                    continue;
                }

                $factKeys = array_values(array_filter(
                    is_array($question['fact_keys'] ?? null) ? $question['fact_keys'] : [],
                    static fn (mixed $factKey): bool => is_string($factKey),
                ));

                if (! in_array($key, $factKeys, true)) {
                    continue;
                }

                $matches[] = $question + [
                    'family_id' => $family['id'] ?? null,
                    'family_label' => $family['label'] ?? null,
                    'question_bank_version' => $this->version(),
                ];
            }
        }

        if ($matches === []) {
            return null;
        }

        usort($matches, static function (array $left, array $right): int {
            return ((int) ($left['priority'] ?? PHP_INT_MAX)) <=> ((int) ($right['priority'] ?? PHP_INT_MAX));
        });

        return $matches[0];
    }

    /** @return list<array<string, mixed>> */
    private function familyRows(): array
    {
        $families = $this->load()['families'] ?? [];

        if (! is_array($families)) {
            return [];
        }

        return array_values(array_filter(
            $families,
            static fn (mixed $family): bool => is_array($family),
        ));
    }

    /** @param array<string, mixed> $family */
    private function familyMatches(array $family, string $needle): bool
    {
        foreach (['id', 'source_group', 'category_name', 'label'] as $field) {
            $value = $family[$field] ?? null;

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $candidate = $this->normalize($value);

            if ($candidate === $needle
                || str_contains($candidate, $needle)
                || str_contains($needle, $candidate)) {
                return true;
            }
        }

        return false;
    }

    /** @return array{version?:mixed,families?:mixed} */
    private function load(): array
    {
        if ($this->data !== null) {
            return $this->data;
        }

        $path = $this->path ?? resource_path('data/ai-question-bank.json');

        if (! is_file($path) || ! is_readable($path)) {
            return $this->data = [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return $this->data = is_array($decoded) ? $decoded : [];
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ñ' => 'n', 'ü' => 'u',
        ]);
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}
