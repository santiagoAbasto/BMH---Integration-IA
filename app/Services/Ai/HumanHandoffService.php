<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Domain\Customer\DTO\CustomerAccount;
use App\Domain\Search\DTO\Candidate;
use App\Models\Ai\AiConversation;
use App\Models\Ai\AiHandoff;

/**
 * Derivación a un asesor de carne y hueso.
 *
 * No es un mensaje de error: es parte del producto. Un asesor que sabe cuándo
 * no sabe genera más confianza que uno que improvisa.
 */
final class HumanHandoffService
{
    public const LOW_CONFIDENCE     = 'low_confidence';
    public const TOO_MANY           = 'too_many_candidates';
    public const PRICING            = 'pricing_requires_validation';
    public const DATA_CONFLICT      = 'data_conflict';
    public const NOT_FOUND          = 'product_not_found';
    public const AI_ERROR           = 'ai_error';
    public const CUSTOMER_REQUESTED = 'customer_requested';

    public function __construct(
        private readonly ConversationMemoryService $memory,
    ) {
    }

    /**
     * ¿Corresponde derivar automáticamente?
     *
     * @param list<Candidate> $candidates
     * @param bool            $askedAlready      ya se le pidió al menos un dato
     * @param bool            $hasUsefulQuestion queda alguna pregunta que reduzca
     */
    public function shouldHandoff(
        array $candidates,
        bool $askedAlready,
        bool $hasUsefulQuestion = false,
        ?string $priceStatus = null,
    ): ?string {
        if ($priceStatus !== null && $priceStatus !== 'verified') {
            return self::PRICING;
        }

        $tooMany = (int) config('bmh.handoff.too_many_candidates_threshold', 40);

        // Demasiados candidatos SÓLO es motivo de derivación si además no hay
        // ninguna pregunta que los parta. Con una buena pregunta disponible,
        // preguntar es mejor que derivar.
        if (count($candidates) > $tooMany && ! $hasUsefulQuestion) {
            return self::TOO_MANY;
        }

        if ($candidates === [] && $askedAlready) {
            return self::NOT_FOUND;
        }

        /*
         * Baja confianza dispara derivación sólo cuando ya pedimos un dato Y no
         * queda ninguna pregunta útil. Mientras el desambiguador tenga algo que
         * preguntar, seguir preguntando es lo correcto: derivar ahí sería
         * renunciar teniendo el camino disponible.
         */
        if ($askedAlready
            && ! $hasUsefulQuestion
            && $candidates !== []
            && $candidates[0]->confidenceBand() === 'low') {
            return self::LOW_CONFIDENCE;
        }

        return null;
    }

    /**
     * Deja la consulta preparada para el asesor.
     *
     * El resumen incluye lo que se sabe y lo que falta, para que la persona no
     * tenga que releer toda la conversación.
     *
     * @param list<Candidate> $candidates
     */
    public function open(
        AiConversation $conversation,
        CustomerAccount $customer,
        string $reason,
        array $candidates = [],
    ): AiHandoff {
        $facts = $this->memory->facts($conversation);

        $handoff = AiHandoff::query()->create([
            'conversation_id' => $conversation->id,
            'customer_id'     => $customer->id,
            'seller_id'       => $customer->sellerId,
            'reason'          => $reason,
            'summary'         => $this->summarize($reason, $facts, $candidates),
            'context'         => [
                'facts'      => $facts,
                'candidates' => array_map(
                    static fn (Candidate $c): array => [
                        'product_id' => $c->product->id,
                        'code'       => $c->product->code,
                        'name'       => $c->product->name,
                        'confidence' => round($c->confidence(), 3),
                    ],
                    array_slice($candidates, 0, 5)
                ),
                'commercial_segment' => $customer->commercialSegment(),
            ],
            'status' => 'pending',
        ]);

        $conversation->update(['status' => 'handed_off']);

        return $handoff;
    }

    public function message(): string
    {
        return (string) config('bmh.handoff.message');
    }

    /** @param list<Candidate> $candidates */
    private function summarize(string $reason, array $facts, array $candidates): string
    {
        $lines = ['Motivo: ' . $this->reasonLabel($reason)];

        if ($facts !== []) {
            $lines[] = 'Datos conocidos:';
            foreach ($facts as $key => $fact) {
                $lines[] = sprintf(
                    '  - %s: %s (%s)',
                    $key,
                    $fact['value'],
                    $fact['state'] === 'confirmed' ? 'confirmado' : 'inferido',
                );
            }
        } else {
            $lines[] = 'No se llegó a determinar ningún dato técnico.';
        }

        if ($candidates !== []) {
            $lines[] = 'Candidatos considerados:';
            foreach (array_slice($candidates, 0, 5) as $candidate) {
                $lines[] = sprintf(
                    '  - [%s] %s (%s)',
                    $candidate->product->code,
                    $candidate->product->name,
                    $candidate->confidenceLabel(),
                );
            }
        }

        return implode("\n", $lines);
    }

    private function reasonLabel(string $reason): string
    {
        return match ($reason) {
            self::LOW_CONFIDENCE     => 'no se pudo identificar la pieza con confianza suficiente',
            self::TOO_MANY           => 'demasiados candidatos y ninguna pregunta los reduce',
            self::PRICING            => 'el precio necesita validación comercial',
            self::DATA_CONFLICT      => 'hay datos inconsistentes en el catálogo',
            self::NOT_FOUND          => 'la pieza no aparece en el catálogo',
            self::AI_ERROR           => 'error del servicio de IA',
            self::CUSTOMER_REQUESTED => 'lo pidió el cliente',
            default                  => $reason,
        };
    }
}
