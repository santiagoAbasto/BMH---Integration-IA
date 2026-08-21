<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Domain\Search\DTO\SearchQuery;
use App\Models\Ai\AiConversation;
use App\Models\Ai\AiCustomerContext;
use App\Services\Ai\DTO\ImageAnalysis;

/**
 * Memoria de la conversación.
 *
 * Guarda hechos, no transcripciones. Y sobre todo distingue estados:
 *
 *  - `confirmed`: lo dijo el cliente explícitamente, o lo confirma la base.
 *  - `inferred`:  lo dedujo el análisis de imagen o la interpretación de texto.
 *  - `unknown`:   se preguntó y no hubo respuesta útil.
 *
 * Una inferencia **nunca** se convierte sola en hecho confirmado. Si el cliente
 * dijo "es Bosch" y después una foto sugiere otra marca, el conflicto queda
 * expuesto en vez de resolverse en silencio.
 *
 * @see docs/ai-architecture.md §"Memoria"
 */
final class ConversationMemoryService
{
    /** Claves que valen la pena recordar entre turnos. */
    private const TRACKED = [
        'category', 'brand', 'model', 'code',
        'voltage', 'amperes', 'diameter', 'total_length', 'inner_diameter',
        'outer_diameter', 'splines', 'teeth', 'slots', 'pins', 'terminals',
        'application', 'rotation', 'type',
    ];

    public function remember(
        AiConversation $conversation,
        string $key,
        string $value,
        string $state,
        string $source,
        float $confidence,
    ): void {
        if (! in_array($key, self::TRACKED, true)) {
            return;
        }

        $value = trim($value);

        if ($value === '') {
            return;
        }

        $existing = AiCustomerContext::query()
            ->where('conversation_id', $conversation->id)
            ->where('fact_key', $key)
            ->first();

        // Un dato confirmado no se pisa con una inferencia posterior.
        if ($existing !== null
            && $existing->state === AiCustomerContext::STATE_CONFIRMED
            && $state !== AiCustomerContext::STATE_CONFIRMED) {
            return;
        }

        /*
         * Entre dos inferencias, gana la más confiable.
         *
         * Sin esto, cada turno pisa el dato anterior aunque sea peor: el cliente
         * dice "un rotor" (ROTORES, 0.9) y dos mensajes después "diámetro 88.8"
         * hace que el modelo sugiera RODAMIENTOS (0.6) y se pierda el rubro
         * correcto. Un dato nuevo tiene que ganarse el lugar, no sólo llegar
         * último.
         */
        if ($existing !== null
            && $existing->state === AiCustomerContext::STATE_INFERRED
            && $state === AiCustomerContext::STATE_INFERRED
            && $confidence < (float) $existing->confidence
            && ! $this->looselyEqual($existing->fact_value, $value)) {
            return;
        }

        AiCustomerContext::query()->updateOrCreate(
            ['conversation_id' => $conversation->id, 'fact_key' => $key],
            [
                'fact_value' => mb_substr($value, 0, 255),
                'state'      => $state,
                'source'     => $source,
                'confidence' => $confidence,
            ],
        );
    }

    /** Lo que el cliente dijo con todas las letras. */
    public function rememberStated(AiConversation $conversation, string $key, string $value): void
    {
        $this->remember($conversation, $key, $value, AiCustomerContext::STATE_CONFIRMED, 'user_stated', 1.0);
    }

    /** Lo que dedujo un modelo. */
    public function rememberInferred(
        AiConversation $conversation,
        string $key,
        string $value,
        string $source,
        float $confidence,
    ): void {
        $this->remember($conversation, $key, $value, AiCustomerContext::STATE_INFERRED, $source, $confidence);
    }

    /**
     * Incorpora un análisis de imagen. Todo entra como inferido.
     *
     * Si la foto contradice una marca ya confirmada, no la pisa: devuelve el
     * conflicto para que el asistente lo pregunte.
     *
     * @return list<array{key:string, confirmed:string, observed:string}> conflictos
     */
    public function absorbImageAnalysis(AiConversation $conversation, ImageAnalysis $analysis): array
    {
        $conflicts = [];

        if ($analysis->brandGuess !== null) {
            $confirmed = $this->confirmedValue($conversation, 'brand');

            if ($confirmed !== null && ! $this->looselyEqual($confirmed, $analysis->brandGuess)) {
                $conflicts[] = [
                    'key'       => 'brand',
                    'confirmed' => $confirmed,
                    'observed'  => $analysis->brandGuess,
                ];
            } else {
                $this->rememberInferred($conversation, 'brand', $analysis->brandGuess, 'ai_vision', $analysis->confidence);
            }
        }

        foreach ($analysis->attributes as $key => $value) {
            $this->rememberInferred($conversation, (string) $key, (string) $value, 'ai_vision', $analysis->confidence);
        }

        // Un código legible en la pieza vale mucho más que un parecido de forma,
        // pero sigue siendo una lectura de OCR: entra como inferido y la base lo
        // confirma o lo descarta.
        if ($analysis->hasCode()) {
            $this->rememberInferred($conversation, 'code', $analysis->visibleCodes[0], 'ai_vision_ocr', max(0.6, $analysis->confidence));
        }

        if ($analysis->categoryHints !== []) {
            $this->rememberInferred($conversation, 'category', $analysis->categoryHints[0], 'ai_vision', $analysis->confidence);
        }

        return $conflicts;
    }

    /**
     * Reconstruye la búsqueda acumulada de la conversación.
     *
     * Los hechos confirmados y los inferidos entran los dos, porque los dos
     * sirven para filtrar. La diferencia importa al momento de AFIRMAR, no al
     * de buscar.
     */
    public function toSearchQuery(AiConversation $conversation): SearchQuery
    {
        $facts = $this->facts($conversation);

        $attributes = [];

        foreach ($facts as $key => $fact) {
            if (in_array($key, ['category', 'brand', 'model', 'code'], true)) {
                continue;
            }

            $attributes[$key] = $fact['value'];
        }

        return new SearchQuery(
            code: $facts['code']['value'] ?? null,
            brand: $facts['brand']['value'] ?? null,
            model: $facts['model']['value'] ?? null,
            attributes: $attributes,
        );
    }

    /** @return array<string, array{value:string, state:string, source:string, confidence:float}> */
    public function facts(AiConversation $conversation): array
    {
        $facts = [];

        foreach ($conversation->facts()->get() as $row) {
            $facts[$row->fact_key] = [
                'value'      => $row->fact_value,
                'state'      => $row->state,
                'source'     => $row->source,
                'confidence' => (float) $row->confidence,
            ];
        }

        return $facts;
    }

    /** Lo que todavía falta saber, para el panel de contexto técnico. */
    public function missingFor(AiConversation $conversation, array $suggestedKeys): array
    {
        $known = array_keys($this->facts($conversation));

        return array_values(array_diff($suggestedKeys, $known));
    }

    public function forget(AiConversation $conversation, string $key): void
    {
        AiCustomerContext::query()
            ->where('conversation_id', $conversation->id)
            ->where('fact_key', $key)
            ->delete();
    }

    private function confirmedValue(AiConversation $conversation, string $key): ?string
    {
        $row = AiCustomerContext::query()
            ->where('conversation_id', $conversation->id)
            ->where('fact_key', $key)
            ->where('state', AiCustomerContext::STATE_CONFIRMED)
            ->first();

        return $row?->fact_value;
    }

    private function looselyEqual(string $a, string $b): bool
    {
        $a = mb_strtoupper(trim($a));
        $b = mb_strtoupper(trim($b));

        return $a === $b || str_contains($a, $b) || str_contains($b, $a);
    }
}
