<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Domain\Catalog\Contracts\CatalogRepositoryInterface;
use App\Domain\Search\DTO\Candidate;
use App\Models\Ai\AiAttachment;
use App\Models\Ai\AiCorrectionRule;
use App\Models\Ai\AiMessageRecord;
use Illuminate\Support\Facades\Storage;

/**
 * Aplica correcciones sólo después de una aprobación humana.
 *
 * La clave de reutilización es deliberadamente estricta: la misma foto
 * (hash del binario) o el mismo texto normalizado. No se intenta "adivinar"
 * que dos fotos distintas son la misma pieza, porque eso volvería a introducir
 * el riesgo que la corrección busca evitar.
 */
final class ApprovedCorrectionService
{
    public function __construct(private readonly CatalogRepositoryInterface $catalog)
    {
    }

    /** @return array{fingerprint:string,kind:string}|null */
    public function fingerprint(string $text, array $attachments = []): ?array
    {
        $imageHashes = [];

        foreach ($attachments as $attachment) {
            if (! $attachment instanceof AiAttachment || $attachment->path === null) {
                continue;
            }

            $path = Storage::disk($attachment->disk)->path($attachment->path);

            if (is_file($path)) {
                $imageHashes[] = hash_file('sha256', $path);
            }
        }

        if ($imageHashes !== []) {
            sort($imageHashes, SORT_STRING);

            return [
                'fingerprint' => hash('sha256', implode('|', $imageHashes)),
                'kind' => 'image',
            ];
        }

        $normalized = $this->normalizeText($text);

        if ($normalized === '') {
            return null;
        }

        return [
            'fingerprint' => hash('sha256', $normalized),
            'kind' => 'text',
        ];
    }

    /** @return array{fingerprint:string,kind:string}|null */
    public function fingerprintForMessage(AiMessageRecord $message): ?array
    {
        return $this->fingerprint(
            $message->content,
            $message->relationLoaded('attachments') ? $message->attachments->all() : $message->attachments()->get()->all(),
        );
    }

    /**
     * @param  list<Candidate> $candidates
     * @return array{candidates:list<Candidate>, note:?string, applied:bool}
     */
    public function apply(string $text, array $attachments, array $candidates): array
    {
        $input = $this->fingerprint($text, $attachments);

        if ($input === null) {
            return ['candidates' => $candidates, 'note' => null, 'applied' => false];
        }

        $rule = AiCorrectionRule::query()
            ->where('active', true)
            ->where('input_fingerprint', $input['fingerprint'])
            ->where('fingerprint_kind', $input['kind'])
            ->latest('approved_at')
            ->first();

        if ($rule === null) {
            return ['candidates' => $candidates, 'note' => null, 'applied' => false];
        }

        $product = $this->catalog->find((int) $rule->expected_product_id);

        if ($product === null || ! $product->condition->isPublic()) {
            return ['candidates' => $candidates, 'note' => null, 'applied' => false];
        }

        $candidate = null;

        foreach ($candidates as $existing) {
            if ($existing->product->id === $product->id) {
                $candidate = $existing;
                break;
            }
        }

        if ($candidate === null) {
            $candidate = new Candidate($product);
            $candidates[] = $candidate;
        }

        // La regla se activa sólo para una entrada idéntica y fue revisada por
        // un administrador. Aun así, Candidate limita la confianza sin código
        // para pedir confirmación al cliente antes de cerrar la identificación.
        $candidate->addSignal('approved_correction', 120.0);
        $candidate->matchedOn[] = 'corrección validada para esta misma consulta';

        usort($candidates, static fn (Candidate $a, Candidate $b): int => $b->score <=> $a->score);

        return [
            'candidates' => $candidates,
            'note' => 'Se priorizó una corrección validada para esta misma consulta. Confirmá el código antes de agregar al carrito.',
            'applied' => true,
        ];
    }

    private function normalizeText(string $text): string
    {
        return mb_strtoupper((string) preg_replace('/[^\pL\pN]+/u', '', trim($text)));
    }
}
