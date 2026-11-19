<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Domain\Catalog\Contracts\CatalogRepositoryInterface;
use App\Domain\Search\CatalogImageFingerprintIndex;
use App\Domain\Search\DTO\Candidate;
use App\Models\Ai\AiAttachment;
use App\Models\Ai\AiCorrectionRule;
use App\Models\Ai\AiFeedback;
use App\Models\Ai\AiMessageRecord;
use App\Services\Ai\Support\ImagePayload;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Lo que el asesor aprendió de personas, no del proveedor de IA.
 *
 * Una regla liga una ENTRADA (una foto/captura o un texto) a un producto del
 * catálogo. Nace de tres fuentes, de menor a mayor confianza:
 *
 *  - `feedback`     un 👎 de un cliente con el código correcto, aprobado por
 *                   un administrador;
 *  - `demo_account` un 👍/👎 de una cuenta demo (probadores de confianza),
 *                   aprendido sin esperar revisión;
 *  - `admin_teach`  una enseñanza directa del administrador.
 *
 * Reconocer la entrada es deliberadamente estricto:
 *  - la misma foto exacta (hash del binario), o
 *  - la misma foto reenviada, recomprimida o recortada un poco: huella
 *    perceptual con el mismo umbral estricto que el índice del catálogo;
 *  - el mismo texto normalizado.
 * Nunca se adivina que dos fotos distintas son la misma pieza.
 */
final class ApprovedCorrectionService
{
    /**
     * Umbral de una regla enseñada. Más estricto que el del índice del catálogo
     * (que sólo propone candidatos para confirmar): una regla empuja un
     * producto al primer lugar, así que no puede equivocarse por parecido.
     */
    private const RULE_MAX_DISTANCE = 8;

    private const RULE_MAX_DISTANCE_PER_HASH = 6;

    public function __construct(
        private readonly CatalogRepositoryInterface $catalog,
        private readonly CatalogImageFingerprintIndex $images,
    ) {
    }

    /**
     * @param  list<AiAttachment>  $attachments
     * @return array{fingerprint:string,kind:string,hashes:list<array{average_hash:string,difference_hash:string}>}|null
     */
    public function fingerprint(string $text, array $attachments = []): ?array
    {
        $imageHashes = [];
        $perceptual = [];

        foreach ($this->imageFiles($attachments) as $path) {
            $imageHashes[] = hash_file('sha256', $path);

            if (($hash = $this->images->fingerprints($path)) !== null) {
                $perceptual[] = $hash;
            }
        }

        if ($imageHashes !== []) {
            sort($imageHashes, SORT_STRING);

            return [
                'fingerprint' => hash('sha256', implode('|', $imageHashes)),
                'kind' => 'image',
                // Con varias fotos sólo cuenta la combinación exacta: una sola
                // foto parecida no alcanza para dar por repetida la consulta.
                'hashes' => count($imageHashes) === 1 ? $perceptual : [],
            ];
        }

        $normalized = $this->normalizeText($text);

        if ($normalized === '') {
            return null;
        }

        return [
            'fingerprint' => hash('sha256', $normalized),
            'kind' => 'text',
            'hashes' => [],
        ];
    }

    /**
     * La misma huella que tendría un cliente que envía esa imagen sola.
     *
     * @return array{fingerprint:string,kind:string,hashes:list<array{average_hash:string,difference_hash:string}>}|null
     */
    public function fingerprintFile(string $path): ?array
    {
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $perceptual = $this->images->fingerprints($path);

        return [
            'fingerprint' => hash('sha256', (string) hash_file('sha256', $path)),
            'kind' => 'image',
            'hashes' => $perceptual === null ? [] : [$perceptual],
        ];
    }

    /** @return array{fingerprint:string,kind:string,hashes:list<array{average_hash:string,difference_hash:string}>}|null */
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

        $rule = $this->findRule($input);

        if ($rule === null) {
            return ['candidates' => $candidates, 'note' => null, 'applied' => false];
        }

        $product = $this->catalog->find((int) $rule->expected_product_id);

        if ($product === null || ! $product->condition->isPublic()) {
            return ['candidates' => $candidates, 'note' => null, 'applied' => false];
        }

        AiCorrectionRule::query()->whereKey($rule->id)->update([
            'hits' => $rule->hits + 1,
            'last_hit_at' => now(),
        ]);

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

        // La regla se activa sólo para una entrada ya vista y validada por una
        // persona. Aun así, Candidate limita la confianza sin código para pedir
        // confirmación al cliente antes de cerrar la identificación.
        $candidate->addSignal('approved_correction', 120.0);
        $candidate->matchedOn[] = 'corrección validada para esta misma consulta';

        usort($candidates, static fn (Candidate $a, Candidate $b): int => $b->score <=> $a->score);

        return [
            'candidates' => $candidates,
            'note' => 'Se priorizó una corrección validada para esta misma consulta. Confirmá el código antes de agregar al carrito.',
            'applied' => true,
        ];
    }

    /**
     * Fotos REALES que el equipo enseñó para productos de estos rubros.
     *
     * Una regla sólo reconoce la misma foto; pero la foto enseñada de un estator
     * sirve de referencia para otra foto del mismo estator, tomada desde otro
     * ángulo. El asesor las suma como candidatos y las compara con la foto nueva.
     * Un producto por rubro-consulta cuenta una vez (la enseñanza más reciente).
     *
     * @param  list<int>  $categoryIds
     * @return list<array{product: \App\Domain\Catalog\DTO\ProductView, path: string}>
     */
    public function taughtExamples(array $categoryIds, int $limit = 6): array
    {
        if ($categoryIds === []) {
            return [];
        }

        $rules = AiCorrectionRule::query()
            ->where('active', true)
            ->where('fingerprint_kind', 'image')
            ->whereNotNull('reference_path')
            ->latest('approved_at')
            ->latest('id')
            ->limit(100)
            ->get();

        if ($rules->isEmpty()) {
            return [];
        }

        $products = [];

        foreach ($this->catalog->findMany($rules->pluck('expected_product_id')->map(static fn ($id): int => (int) $id)->unique()->values()->all()) as $product) {
            $products[$product->id] = $product;
        }

        $disk = Storage::disk((string) config('bmh.attachments.disk', 'ai_private'));
        $examples = [];
        $seen = [];

        foreach ($rules as $rule) {
            $productId = (int) $rule->expected_product_id;
            $product = $products[$productId] ?? null;

            if (isset($seen[$productId])
                || $product === null
                || ! $product->condition->isPublic()
                || ! in_array($product->category?->id, $categoryIds, true)
                || ! $disk->exists((string) $rule->reference_path)) {
                continue;
            }

            $seen[$productId] = true;
            $examples[] = ['product' => $product, 'path' => $disk->path((string) $rule->reference_path)];

            if (count($examples) >= $limit) {
                break;
            }
        }

        return $examples;
    }

    /**
     * Aprende de un feedback ya guardado. Devuelve la regla, o null si no hay
     * qué aprender (👎 sin código esperado, entrada irreconstruible, producto
     * que ya no es público).
     */
    public function rememberFromFeedback(AiFeedback $feedback, string $source, ?int $adminId = null): ?AiCorrectionRule
    {
        $productId = $feedback->was_correct ? $feedback->product_id : $feedback->expected_product_id;

        if ($productId === null) {
            return null;
        }

        $message = $this->inputMessageFor($feedback);

        if ($message === null) {
            return null;
        }

        $attachments = $message->attachments()->get()->all();
        $input = $this->fingerprint($message->content, $attachments);

        if ($input === null) {
            return null;
        }

        $files = $this->imageFiles($attachments);

        return $this->remember(
            $input,
            (int) $productId,
            $source,
            $feedback->id,
            $adminId,
            null,
            $files[0] ?? null,
        );
    }

    /**
     * Crea (o reactiva) la regla entrada → producto.
     *
     * @param  array{fingerprint:string,kind:string,hashes:list<array{average_hash:string,difference_hash:string}>}  $input
     */
    public function remember(
        array $input,
        int $productId,
        string $source,
        ?int $feedbackId = null,
        ?int $adminId = null,
        ?string $note = null,
        ?string $referenceFile = null,
    ): ?AiCorrectionRule {
        $product = $this->catalog->find($productId);

        if ($product === null || ! $product->condition->isPublic()) {
            return null;
        }

        $rule = AiCorrectionRule::query()->firstOrNew([
            'input_fingerprint' => $input['fingerprint'],
            'fingerprint_kind' => $input['kind'],
            'expected_product_id' => $productId,
        ]);

        if (! $rule->exists) {
            $rule->feedback_id = $feedbackId;
            $rule->source = $source;
            $rule->reference_path = $input['kind'] === 'image' ? $this->storeReference($referenceFile) : null;
            $rule->average_hash = $input['hashes'][0]['average_hash'] ?? null;
            $rule->difference_hash = $input['hashes'][0]['difference_hash'] ?? null;
        }

        $rule->active = true;
        $rule->approved_at = now();
        $rule->approved_by_admin_id = $adminId ?? $rule->approved_by_admin_id;
        $rule->note = $note ?? $rule->note;
        $rule->save();

        return $rule;
    }

    /** La consulta del cliente a la que respondió el mensaje evaluado. */
    public function inputMessageFor(AiFeedback $feedback): ?AiMessageRecord
    {
        if ($feedback->message_id === null) {
            return null;
        }

        return AiMessageRecord::query()
            ->where('conversation_id', $feedback->conversation_id)
            ->where('role', 'user')
            ->where('id', '<', $feedback->message_id)
            ->with('attachments')
            ->latest('id')
            ->first();
    }

    /** @param  array{fingerprint:string,kind:string,hashes:list<array{average_hash:string,difference_hash:string}>}  $input */
    private function findRule(array $input): ?AiCorrectionRule
    {
        $exact = AiCorrectionRule::query()
            ->where('active', true)
            ->where('input_fingerprint', $input['fingerprint'])
            ->where('fingerprint_kind', $input['kind'])
            ->latest('approved_at')
            ->latest('id')
            ->first();

        if ($exact !== null || $input['kind'] !== 'image' || $input['hashes'] === []) {
            return $exact;
        }

        // Misma foto, otro archivo: reenviada por WhatsApp, recomprimida o con
        // un borde distinto.
        $maximum = self::RULE_MAX_DISTANCE;
        $maximumPerHash = self::RULE_MAX_DISTANCE_PER_HASH;

        $best = null;
        $bestDistance = PHP_INT_MAX;

        $candidates = AiCorrectionRule::query()
            ->where('active', true)
            ->where('fingerprint_kind', 'image')
            ->whereNotNull('average_hash')
            ->whereNotNull('difference_hash')
            ->latest('approved_at')
            ->get();

        foreach ($candidates as $rule) {
            foreach ($input['hashes'] as $hash) {
                $average = CatalogImageFingerprintIndex::hamming($hash['average_hash'], (string) $rule->average_hash);
                $difference = CatalogImageFingerprintIndex::hamming($hash['difference_hash'], (string) $rule->difference_hash);
                $distance = $average + $difference;

                if ($distance > $maximum || $average > $maximumPerHash || $difference > $maximumPerHash) {
                    continue;
                }

                if ($distance < $bestDistance) {
                    $best = $rule;
                    $bestDistance = $distance;
                }
            }
        }

        return $best;
    }

    /**
     * @param  list<AiAttachment>  $attachments
     * @return list<string> rutas absolutas legibles
     */
    private function imageFiles(array $attachments): array
    {
        $files = [];

        foreach ($attachments as $attachment) {
            if (! $attachment instanceof AiAttachment || $attachment->path === null) {
                continue;
            }

            $path = Storage::disk($attachment->disk)->path($attachment->path);

            if (is_file($path)) {
                $files[] = $path;
            }
        }

        return $files;
    }

    /**
     * Guarda una copia de la imagen que originó la regla, para que el
     * administrador la vea y para que la regla sobreviva a que se borre la
     * conversación del cliente.
     */
    private function storeReference(?string $file): ?string
    {
        if ($file === null || ! is_file($file)) {
            return null;
        }

        [$mime, $base64] = ImagePayload::encode($file, 1280);
        $extension = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
        $path = 'learning/'.Str::uuid().'.'.$extension;

        Storage::disk(config('bmh.attachments.disk', 'ai_private'))->put($path, base64_decode($base64));

        return $path;
    }

    private function normalizeText(string $text): string
    {
        return mb_strtoupper((string) preg_replace('/[^\pL\pN]+/u', '', trim($text)));
    }
}
