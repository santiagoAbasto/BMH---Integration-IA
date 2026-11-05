<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Catalog\Contracts\CatalogRepositoryInterface;
use App\Domain\Customer\Contracts\CustomerRepositoryInterface;
use App\Domain\Customer\DTO\CustomerAccount;
use App\Domain\Pricing\PricingEngine;
use App\Http\Controllers\Controller;
use App\Http\Requests\Assistant\AddAssistantCartItemRequest;
use App\Http\Requests\Assistant\SendMessageRequest;
use App\Http\Requests\Assistant\StoreAttachmentRequest;
use App\Http\Requests\Assistant\StoreFeedbackRequest;
use App\Models\Ai\AiAttachment;
use App\Models\Ai\AiCartOperation;
use App\Models\Ai\AiConversation;
use App\Models\Ai\AiFeedback;
use App\Services\Ai\AiProviderManager;
use App\Services\Ai\AssistantCartService;
use App\Services\Ai\AttachmentService;
use App\Services\Ai\ConversationOrchestrator;
use App\Services\Ai\HumanHandoffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * API del asesor.
 *
 * Controller delgado: autentica, valida, delega en el orquestador y devuelve
 * JSON. Ninguna regla de negocio vive acá.
 *
 * El cliente se resuelve SIEMPRE desde la sesión (`Auth::guard('web')`). Nunca
 * se acepta un `customer_id` que venga del frontend.
 */
final class AssistantController extends Controller
{
    public function __construct(
        private readonly ConversationOrchestrator $orchestrator,
        private readonly CatalogRepositoryInterface $catalog,
        private readonly CustomerRepositoryInterface $customers,
        private readonly PricingEngine $pricing,
        private readonly AttachmentService $attachments,
        private readonly HumanHandoffService $handoff,
        private readonly AiProviderManager $providers,
        private readonly AssistantCartService $cart,
    ) {
    }

    /**
     * Estado del asesor + contexto que el widget carga al abrirse.
     *
     * El historial no viaja en el layout de cada página: se pide acá, una sola
     * vez, cuando el cliente efectivamente abre el asesor.
     */
    public function status(): JsonResponse
    {
        $customer = $this->customer();

        $recentPurchases = config('bmh.features.customer_history')
            ? collect(app(\App\Domain\Orders\Contracts\OrderHistoryRepositoryInterface::class)
                ->linesForCustomer($customer->id, 12))
                ->unique('productId')
                ->take(6)
                ->values()
                ->map(static fn ($line): array => [
                    'product_id' => $line->productId,
                    'code' => $line->productCode,
                    'name' => $line->productName,
                    'category' => $line->categoryName,
                ])
                ->all()
            : [];

        return response()->json([
            'recentPurchases' => $recentPurchases,
            'features' => [
                'ai' => (bool) config('bmh.features.ai'),
                'vision' => (bool) config('bmh.features.vision'),
                'history' => (bool) config('bmh.features.customer_history'),
                'debug' => (bool) config('bmh.features.debug'),
            ],
            'provider' => $this->providers->describe(),
            'inventory' => [
                'can_assert_availability' => (bool) config('bmh.inventory.semantics_verified'),
            ],
        ]);
    }

    public function index(): JsonResponse
    {
        $customer = $this->customer();

        $conversations = AiConversation::query()
            ->customerFacing()
            ->ownedBy($customer->id)
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get(['id', 'title', 'status', 'detected_intent', 'updated_at']);

        return response()->json(['conversations' => $conversations]);
    }

    public function store(): JsonResponse
    {
        $customer = $this->customer();

        $conversation = AiConversation::query()->create([
            'customer_id' => $customer->id,
            'mode' => 'customer',
            'status' => 'active',
            'prompt_version' => (string) config('bmh.ai.prompt_version'),
        ]);

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'title' => null,
                'status' => $conversation->status,
            ],
            'greeting' => sprintf(
                'Hola%s. ¿Qué pieza necesitás? Podés escribirme, pegar un código o mandarme una foto.',
                $customer->displayName !== 'Cliente' ? ', '.$this->firstName($customer->displayName) : '',
            ),
        ], 201);
    }

    public function show(int $conversationId): JsonResponse
    {
        $customer = $this->customer();
        $conversation = $this->conversation($conversationId);

        $records = $conversation->messages()
            ->with(['candidates', 'attachments'])
            ->orderBy('id')
            ->get();

        $messageIds = $records->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $productIds = $records->flatMap(
            static fn ($message) => $message->candidates->pluck('product_id'),
        )->map(static fn ($id): int => (int) $id)->unique()->values()->all();

        $products = collect($this->catalog->findMany($productIds))->keyBy('id');
        $decisions = AiFeedback::query()
            ->where('conversation_id', $conversation->id)
            ->where('customer_id', $customer->id)
            ->whereIn('message_id', $messageIds)
            ->orderBy('id')
            ->get()
            ->groupBy('message_id')
            ->map(static fn ($rows) => $rows->last());
        $cartProducts = AiCartOperation::query()
            ->where('conversation_id', $conversation->id)
            ->where('customer_id', $customer->id)
            ->where('status', 'completed')
            ->whereIn('message_id', $messageIds)
            ->get()
            ->groupBy('message_id')
            ->map(static fn ($rows): array => $rows->pluck('product_id')->map(static fn ($id): int => (int) $id)->all());

        $messages = $records->map(function ($message) use ($cartProducts, $customer, $decisions, $products): array {
            $metadata = $this->publicMetadata($message->metadata);
            $decision = $decisions->get($message->id);

            $candidates = $message->candidates
                ->take((int) config('bmh.ranking.max_presented', 3))
                ->map(function ($candidate) use ($customer, $products): ?array {
                    $product = $products->get((int) $candidate->product_id);

                    if ($product === null || ! $product->condition->isPublic()) {
                        return null;
                    }

                    $band = (string) $candidate->confidence_band;
                    $quote = $customer->enabled
                        ? $this->pricing->quote($product->id, $customer, $product->category?->id)
                        : null;
                    $debugSignals = (array) ($candidate->signals ?? []);
                    $debugScore = array_sum(array_diff_key($debugSignals, ['vision_ocr_confidence' => true]));

                    return [
                        'product' => $product->jsonSerialize(),
                        'confidence' => (float) $candidate->confidence,
                        'confidence_band' => $band,
                        'confidence_label' => (string) config('bmh.confidence.labels.'.$band, 'Coincidencia'),
                        'confidence_short' => (string) config('bmh.confidence.short_labels.'.$band, 'Resultado'),
                        'matched_on' => [],
                        'price' => $quote?->jsonSerialize(),
                        'debug' => config('bmh.features.debug') ? [
                            'score' => round($debugScore, 3),
                            'signals' => $debugSignals,
                        ] : null,
                    ];
                })
                ->filter()
                ->values();

            return [
                'id' => $message->id,
                'role' => $message->role,
                'content' => $message->content,
                'created_at' => $message->created_at?->toIso8601String(),
                'metadata' => $metadata,
                'candidates' => $candidates,
                'candidate_count' => (int) ($metadata['candidate_count'] ?? $message->candidates->count()),
                'next_question' => $metadata['next_question'] ?? null,
                'price' => $metadata['price'] ?? null,
                'handoff' => $metadata['handoff'] ?? null,
                'conflicts' => $metadata['conflicts'] ?? [],
                'vision' => $metadata['vision'] ?? [],
                'vision_notes' => $metadata['vision_notes'] ?? [],
                'feedback_given' => $decision !== null,
                'confirmed_product_id' => $decision?->was_correct ? (int) $decision->product_id : null,
                'cart_added_product_ids' => $cartProducts->get($message->id, []),
                'attachments' => $message->attachments->map(fn (AiAttachment $a): array => [
                    'id' => $a->id,
                    'url' => route('assistant.attachment', ['attachment' => $a->id]),
                ]),
            ];
        });

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'status' => $conversation->status,
            ],
            'messages' => $messages,
        ]);
    }

    /** Un turno de conversación. */
    public function message(SendMessageRequest $request, int $conversationId): JsonResponse
    {
        $customer = $this->customer();
        $conversation = $this->conversation($conversationId);

        $attachments = $this->resolveAttachments($conversation, $request->validated('attachment_ids', []));

        // Mismo motivo que en `stream()`: un turno con foto lleva varias llamadas
        // al modelo y no entra en los 30 segundos que fija el php.ini heredado.
        set_time_limit(0);

        $result = $this->orchestrator->handle(
            $conversation,
            $customer,
            (string) $request->validated('message', ''),
            $attachments,
        );

        return response()->json($result);
    }

    /**
     * Respuesta en streaming.
     *
     * Importante: el precio y los candidatos se resuelven ANTES de emitir nada.
     * No se hace streaming de un número provisional que después cambie.
     */
    public function stream(SendMessageRequest $request, int $conversationId): StreamedResponse
    {
        $customer = $this->customer();
        $conversation = $this->conversation($conversationId);
        $attachments = $this->resolveAttachments($conversation, $request->validated('attachment_ids', []));
        $text = (string) $request->validated('message', '');

        /*
         * Un turno con foto puede tardar más que una request normal: análisis de
         * imagen + comparación contra el catálogo son varias llamadas al modelo.
         *
         * El `public/php.ini` que vino del hosting fija max_execution_time=30 y
         * `artisan serve` lo levanta, así que sin esto PHP mata el proceso a la
         * mitad del stream y el widget se queda girando para siempre.
         */
        set_time_limit(0);
        ignore_user_abort(true);

        return response()->stream(function () use ($conversation, $customer, $text, $attachments): void {
            $emit = static function (string $event, array $data): void {
                echo 'event: '.$event."\n";
                echo 'data: '.json_encode($data, JSON_UNESCAPED_UNICODE)."\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };

            $emit('status', ['state' => 'analyzing', 'label' => $attachments === [] ? 'Buscando en catálogo…' : 'Analizando imagen…']);

            try {
                $result = $this->orchestrator->handle($conversation, $customer, $text, $attachments);
            } catch (\Throwable $e) {
                report($e);
                $emit('error', ['message' => 'Tuvimos un problema procesando la consulta. Probá de nuevo o pedí un asesor.']);

                return;
            }

            // Los datos duros primero: la UI puede pintar las cards mientras se
            // escribe el texto.
            $emit('data', [
                'candidates' => $result['candidates'],
                'candidate_count' => $result['candidate_count'],
                'price' => $result['price'],
                'context' => $result['context'],
                'next_question' => $result['next_question'],
                'handoff' => $result['handoff'],
                // Lo que se leyó en la foto: se muestra al cliente para que vea
                // que el sistema realmente la miró.
                'vision' => $result['vision'] ?? [],
                'vision_notes' => $result['vision_notes'] ?? [],
            ]);

            foreach ($this->chunks((string) $result['message']['content']) as $chunk) {
                $emit('token', ['text' => $chunk]);
                usleep(12000);
            }

            $emit('done', ['message' => $result['message'], 'debug' => $result['debug'] ?? null]);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function attachmentStore(StoreAttachmentRequest $request, int $conversationId): JsonResponse
    {
        $conversation = $this->conversation($conversationId);

        $stored = [];
        $errors = [];

        foreach ($request->file('images', []) as $file) {
            $result = $this->attachments->store($conversation, $file);

            if ($result['ok'] && $result['attachment'] !== null) {
                $stored[] = [
                    'id' => $result['attachment']->id,
                    'url' => route('assistant.attachment', ['attachment' => $result['attachment']->id]),
                ];
            } else {
                $errors[] = $result['error'];
            }
        }

        return response()->json(['attachments' => $stored, 'errors' => $errors], $stored === [] ? 422 : 201);
    }

    /**
     * Entrega un adjunto.
     *
     * El disco es privado: esta ruta es el único acceso, y valida que la
     * conversación sea del cliente autenticado.
     */
    public function attachmentShow(int $attachmentId)
    {
        $customer = $this->customer();

        $attachment = AiAttachment::query()->findOrFail($attachmentId);

        $conversation = AiConversation::query()
            ->customerFacing()
            ->ownedBy($customer->id)
            ->findOrFail($attachment->conversation_id);

        $path = $attachment->thumbnail_path ?? $attachment->path;

        abort_unless(Storage::disk($attachment->disk)->exists($path), 404);

        return response()->file(Storage::disk($attachment->disk)->path($path));
    }

    public function feedback(StoreFeedbackRequest $request, int $conversationId): JsonResponse
    {
        $customer = $this->customer();
        $conversation = $this->conversation($conversationId);

        $messageId = (int) $request->validated('message_id');
        $productId = $request->validated('product_id');
        $wasCorrect = (bool) $request->validated('was_correct');
        [$expectedProductId, $expectedCode] = $this->expectedProduct(
            $request->validated('expected_product_id'),
            $request->validated('expected_code'),
        );

        $message = $conversation->messages()
            ->where('role', 'assistant')
            ->findOrFail($messageId);

        if ($productId !== null && ! $message->candidates()->where('product_id', (int) $productId)->exists()) {
            return response()->json([
                'message' => 'La pieza elegida no pertenece a los resultados de ese mensaje.',
                'code' => 'candidate_mismatch',
            ], 422);
        }

        if ($wasCorrect && $productId === null) {
            return response()->json([
                'message' => 'Elegí qué pieza confirmás.',
                'code' => 'product_required',
            ], 422);
        }

        if ($wasCorrect && ($expectedProductId !== null || $expectedCode !== null)) {
            return response()->json([
                'message' => 'La corrección esperada sólo corresponde cuando marcás que el resultado no fue correcto.',
                'code' => 'unexpected_correction',
            ], 422);
        }

        AiFeedback::query()->create([
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'customer_id' => $customer->id,
            'product_id' => $productId,
            'was_correct' => $wasCorrect,
            'comment' => $request->validated('comment'),
            'expected_product_id' => $wasCorrect ? null : $expectedProductId,
            'expected_code' => $wasCorrect ? null : $expectedCode,
            'reason' => $wasCorrect ? null : $request->validated('reason'),
            'review_status' => $wasCorrect ? 'confirmed' : 'pending',
        ]);

        // Si acertó, se deja registrado el artículo resuelto: sirve para
        // cotizar después sin volver a buscar.
        if ($wasCorrect && $productId !== null) {
            $conversation->update([
                'resolved_product_id' => (int) $productId,
                'status' => 'resolved',
            ]);
        } else {
            $conversation->update([
                'resolved_product_id' => null,
                'status' => 'active',
            ]);
        }

        return response()->json([
            'ok' => true,
            'confirmed_product_id' => $wasCorrect ? (int) $productId : null,
        ]);
    }

    /**
     * La corrección negativa puede apuntar a una pieza que el asesor ni siquiera
     * mostró. Es justamente el dato que necesitamos conservar. El código se
     * valida contra el catálogo y se normaliza a su forma canónica.
     *
     * @return array{0:?int,1:?string}
     */
    private function expectedProduct(mixed $productId, mixed $code): array
    {
        $expectedId = is_numeric($productId) ? (int) $productId : null;
        $expectedCode = is_string($code) && trim($code) !== '' ? trim($code) : null;

        if ($expectedId === null && $expectedCode === null) {
            return [null, null];
        }

        if ($expectedId !== null) {
            $product = $this->catalog->find($expectedId);

            if ($product === null) {
                abort(response()->json([
                    'message' => 'El producto indicado no existe en el catálogo.',
                    'code' => 'expected_product_not_found',
                ], 422));
            }

            if ($expectedCode !== null && $this->foldCode($expectedCode) !== $this->foldCode($product->code)) {
                abort(response()->json([
                    'message' => 'El código indicado no corresponde al producto esperado.',
                    'code' => 'expected_code_mismatch',
                ], 422));
            }

            return [$product->id, $product->code];
        }

        $products = $this->productsByCodeOrReadableVariant((string) $expectedCode);

        if (count($products) !== 1) {
            abort(response()->json([
                'message' => $products === []
                    ? 'No encontramos ese código en el catálogo.'
                    : 'Ese código puede corresponder a más de un producto. Indicá el código completo.',
                'code' => $products === [] ? 'expected_code_not_found' : 'expected_code_ambiguous',
            ], 422));
        }

        return [$products[0]->id, $products[0]->code];
    }

    /** @return list<\App\Domain\Catalog\DTO\ProductView> */
    private function productsByCodeOrReadableVariant(string $code): array
    {
        $normalized = $this->foldCode($code);
        $exact = $this->catalog->findByCode($code);

        if ($exact === []) {
            $exact = $this->catalog->findByNormalizedCode($code);
        }

        /** @var array<int,\App\Domain\Catalog\DTO\ProductView> $hits */
        $hits = [];

        foreach ($exact as $product) {
            $hits[$product->id] = $product;
        }

        // En fotos y pantallas la O y el 0 se confunden con facilidad. Sólo
        // se acepta una variante si resuelve un único artículo real.
        $variants = [$normalized];

        for ($index = 0; $index < strlen($normalized) && count($variants) < 16; $index++) {
            $character = $normalized[$index];

            if ($character !== '0' && $character !== 'O') {
                continue;
            }

            foreach ($variants as $variant) {
                if (count($variants) >= 16) {
                    break 2;
                }

                $alternate = substr_replace($variant, $character === '0' ? 'O' : '0', $index, 1);

                if (! in_array($alternate, $variants, true)) {
                    $variants[] = $alternate;
                }
            }
        }

        foreach ($variants as $variant) {
            foreach ($this->catalog->findByNormalizedCode($variant) as $product) {
                $hits[$product->id] = $product;
            }
        }

        return array_values($hits);
    }

    private function foldCode(string $code): string
    {
        return mb_strtoupper((string) preg_replace('/[^\pL\pN]+/u', '', trim($code)));
    }

    /** Agrega una pieza confirmada; producto y precio se verifican otra vez en servidor. */
    public function cartItemStore(AddAssistantCartItemRequest $request, int $conversationId): JsonResponse
    {
        $user = Auth::guard('web')->user();

        if ($user?->rol !== 'cliente') {
            return response()->json([
                'message' => 'Para agregar desde el asesor, primero debe estar identificado el cliente comprador.',
                'code' => 'customer_required_for_cart',
            ], 422);
        }

        $customer = $this->customer();
        $conversation = $this->conversation($conversationId);

        $result = $this->cart->add(
            $conversation,
            $customer,
            (int) $request->validated('message_id'),
            (int) $request->validated('product_id'),
            (int) $request->validated('quantity'),
        );

        return response()->json($result, 201);
    }

    public function handoff(Request $request, int $conversationId): JsonResponse
    {
        $customer = $this->customer();
        $conversation = $this->conversation($conversationId);

        $record = $this->handoff->open(
            $conversation,
            $customer,
            HumanHandoffService::CUSTOMER_REQUESTED,
        );

        return response()->json([
            'handoff' => ['id' => $record->id, 'status' => $record->status],
            'message' => $this->handoff->message(),
        ], 201);
    }

    // -----------------------------------------------------------------

    private function customer(): CustomerAccount
    {
        $user = Auth::guard('web')->user();

        abort_if($user === null, 401, 'No autenticado.');

        $customer = $this->customers->find((int) $user->id);

        abort_if($customer === null, 403, 'Cliente no encontrado.');

        return $customer;
    }

    /** Carga la conversación verificando que sea del cliente autenticado. */
    private function conversation(int $id): AiConversation
    {
        return AiConversation::query()
            ->customerFacing()
            ->ownedBy($this->customer()->id)
            ->findOrFail($id);
    }

    /** @return list<AiAttachment> */
    private function resolveAttachments(AiConversation $conversation, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return AiAttachment::query()
            ->where('conversation_id', $conversation->id)
            ->whereIn('id', array_map('intval', $ids))
            ->whereNull('message_id')
            ->limit((int) config('bmh.ai.limits.max_images_per_message', 3))
            ->get()
            ->all();
    }

    /** Lo que la metadata puede exponerle al cliente. */
    private function publicMetadata(?array $metadata): array
    {
        if ($metadata === null) {
            return [];
        }

        $public = [
            'next_question' => $metadata['next_question'] ?? null,
            'handoff' => $metadata['handoff'] ?? null,
            'price' => $metadata['price'] ?? null,
            'candidate_count' => $metadata['candidate_count'] ?? null,
            'conflicts' => $metadata['conflicts'] ?? null,
            'vision' => $metadata['vision'] ?? null,
            'vision_notes' => $metadata['vision_notes'] ?? null,
        ];

        if (config('bmh.features.debug')) {
            $public['intent'] = $metadata['intent'] ?? null;
            $public['strategy'] = $metadata['strategy'] ?? null;
        }

        return array_filter($public, static fn ($v) => $v !== null);
    }

    private function firstName(string $name): string
    {
        return explode(' ', trim($name))[0];
    }

    /** @return list<string> */
    private function chunks(string $text): array
    {
        $words = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$text];
        $chunks = [];
        $buffer = '';

        foreach ($words as $word) {
            $buffer .= $word;

            if (mb_strlen($buffer) >= 6) {
                $chunks[] = $buffer;
                $buffer = '';
            }
        }

        if ($buffer !== '') {
            $chunks[] = $buffer;
        }

        return $chunks;
    }
}
