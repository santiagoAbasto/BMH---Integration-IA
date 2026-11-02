<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Catalog\Contracts\CatalogRepositoryInterface;
use App\Domain\Customer\Contracts\CustomerRepositoryInterface;
use App\Domain\Pricing\PricingEngine;
use App\Http\Requests\AiTest\SendAiTestMessageRequest;
use App\Http\Requests\AiTest\StartAiTestConversationRequest;
use App\Http\Requests\AiTest\StoreAiTestAttachmentRequest;
use App\Http\Requests\AiTest\StoreAiTestReviewRequest;
use App\Models\Ai\AiAttachment;
use App\Models\Ai\AiConversation;
use App\Models\Ai\AiMessageRecord;
use App\Models\Ai\AiTestReview;
use App\Services\Ai\AiProviderManager;
use App\Services\Ai\AttachmentService;
use App\Services\Ai\ConversationOrchestrator;
use App\Services\Ai\DiagnosticQuestionBank;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Laboratorio interno para probar el mismo asesor que usa el cliente. */
final class AiTestLabController extends Controller
{
    public function __construct(
        private readonly DiagnosticQuestionBank $questions,
        private readonly CatalogRepositoryInterface $catalog,
        private readonly CustomerRepositoryInterface $customers,
        private readonly PricingEngine $pricing,
        private readonly ConversationOrchestrator $orchestrator,
        private readonly AttachmentService $attachments,
        private readonly AiProviderManager $providers,
    ) {
    }

    public function index(): View
    {
        $adminId = $this->adminId();

        $recent = AiConversation::query()
            ->testsForAdmin($adminId)
            ->latest('updated_at')
            ->limit(20)
            ->get(['id', 'title', 'status', 'test_family', 'customer_id', 'updated_at']);

        return view('backend.ai-test-lab', [
            'ai_test_lab' => true,
            'questionBank' => $this->questionBankPayload(),
            'questionBankVersion' => $this->questions->version(),
            'recentTests' => $recent,
            'defaultCustomerId' => $this->defaultCustomerId(),
            'provider' => $this->providers->describe(),
            'reviewMetrics' => $this->reviewMetrics($adminId),
        ]);
    }

    public function store(StartAiTestConversationRequest $request): JsonResponse
    {
        $family = (string) $request->validated('family');
        $familyData = $this->family($family);

        if ($familyData === null) {
            throw ValidationException::withMessages(['family' => 'La familia seleccionada no existe en el banco de preguntas.']);
        }

        $customerId = $request->validated('customer_id') ?? $this->defaultCustomerId();
        if ($customerId === null) {
            throw ValidationException::withMessages([
                'customer_id' => 'Indicá un cliente de prueba o configurá AI_TEST_CUSTOMER_ID.',
            ]);
        }

        $customer = $this->customers->find((int) $customerId);
        if ($customer === null) {
            throw ValidationException::withMessages(['customer_id' => 'No se encontró ese cliente de contexto.']);
        }

        $conversation = AiConversation::query()->create([
            'customer_id' => $customer->id,
            'mode' => 'test',
            'tester_admin_id' => $this->adminId(),
            'test_family' => $family,
            'title' => 'Prueba · '.$familyData['label'],
            'status' => 'active',
            'prompt_version' => (string) config('bmh.ai.prompt_version'),
        ]);

        return response()->json([
            'conversation' => $this->conversationSummary($conversation),
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->displayName,
                'enabled' => $customer->enabled,
                'segment' => $customer->commercialSegment(),
            ],
            'greeting' => 'Prueba iniciada para '.$familyData['label'].'. Describí el caso como lo haría un cliente.',
            'questions' => $familyData['questions'],
        ], 201);
    }

    public function show(int $conversation): JsonResponse
    {
        $test = $this->conversation($conversation);
        $customer = $this->customers->find($test->customer_id);
        $reviews = AiTestReview::query()
            ->where('conversation_id', $test->id)
            ->where('admin_id', $this->adminId())
            ->get()
            ->keyBy('message_id');

        $records = $test->messages()
            ->with(['candidates', 'attachments'])
            ->orderBy('id')
            ->get();
        $maxPresented = max(0, (int) config('bmh.ranking.max_presented', 3));
        $productIds = $records
            ->flatMap(static fn (AiMessageRecord $message) => $message->candidates
                ->take($maxPresented)
                ->pluck('product_id'))
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
        $products = collect($this->catalog->findMany($productIds))->keyBy('id');

        $messages = $records
            ->map(function (AiMessageRecord $message) use ($customer, $maxPresented, $products, $reviews): array {
                $review = $reviews->get($message->id);
                $candidates = $message->candidates
                    ->take($maxPresented)
                    ->map(function ($candidate) use ($customer, $products): ?array {
                        $product = $products->get((int) $candidate->product_id);

                        if ($product === null) {
                            return null;
                        }

                        $band = (string) $candidate->confidence_band;
                        $quote = $customer?->enabled
                            ? $this->pricing->quote($product->id, $customer, $product->category?->id)
                            : null;
                        $debugSignals = (array) ($candidate->signals ?? []);
                        $payload = [
                            'product' => $product->jsonSerialize(),
                            'confidence' => (float) $candidate->confidence,
                            'confidence_band' => $band,
                            'confidence_label' => (string) config('bmh.confidence.labels.'.$band, 'Coincidencia'),
                            'confidence_short' => (string) config('bmh.confidence.short_labels.'.$band, 'Resultado'),
                            // Las razones legibles no se persistían en los turnos anteriores.
                            'matched_on' => [],
                            'price' => $quote?->jsonSerialize(),
                        ];

                        if (config('bmh.features.debug')) {
                            $payload['debug'] = [
                                'score' => round(array_sum(array_diff_key($debugSignals, ['vision_ocr_confidence' => true])), 3),
                                'signals' => $debugSignals,
                            ];
                        }

                        return $payload;
                    })
                    ->filter()
                    ->values();

                return [
                    'id' => $message->id,
                    'role' => $message->role,
                    'content' => $message->content,
                    'created_at' => $message->created_at?->toIso8601String(),
                    'next_question' => is_array($message->metadata['next_question'] ?? null)
                        ? $message->metadata['next_question']
                        : null,
                    'candidates' => $candidates,
                    'candidate_count' => (int) ($message->metadata['candidate_count'] ?? $message->candidates->count()),
                    'attachments' => $message->attachments->map(fn (AiAttachment $attachment): array => [
                        'id' => $attachment->id,
                        'url' => route('admin.ai-tests.attachments.show', ['attachment' => $attachment->id]),
                    ])->values(),
                    'review' => $review === null ? null : [
                        'rating' => $review->rating,
                        'expected_action' => $review->expected_action,
                        'expected_question_id' => $review->expected_question_id,
                        'expected_product_id' => $review->expected_product_id,
                        'expected_code' => $review->expected_code,
                        'surface' => $review->surface,
                        'severity' => $review->severity,
                        'issue_tags' => $review->issue_tags ?? [],
                        'comment' => $review->comment,
                        'updated_at' => $review->updated_at?->toIso8601String(),
                    ],
                ];
            });

        return response()->json([
            'conversation' => $this->conversationSummary($test),
            'messages' => $messages,
            'questions' => $this->normalizedQuestions($test->test_family ?? ''),
        ]);
    }

    public function message(SendAiTestMessageRequest $request, int $conversation): JsonResponse
    {
        $test = $this->conversation($conversation);
        $customer = $this->customers->find($test->customer_id);
        abort_if($customer === null, 422, 'El cliente de contexto ya no existe.');

        $attachments = $this->resolveAttachments(
            $test,
            $request->validated('attachment_ids', []),
        );

        set_time_limit(0);

        return response()->json($this->orchestrator->handle(
            $test,
            $customer,
            (string) $request->validated('message', ''),
            $attachments,
        ));
    }

    public function attachmentStore(StoreAiTestAttachmentRequest $request, int $conversation): JsonResponse
    {
        $test = $this->conversation($conversation);
        $stored = [];
        $errors = [];

        foreach ($request->file('images', []) as $file) {
            $result = $this->attachments->store($test, $file);

            if ($result['ok'] && $result['attachment'] !== null) {
                $stored[] = [
                    'id' => $result['attachment']->id,
                    'url' => route('admin.ai-tests.attachments.show', ['attachment' => $result['attachment']->id]),
                ];
            } else {
                $errors[] = $result['error'];
            }
        }

        return response()->json(['attachments' => $stored, 'errors' => $errors], $stored === [] ? 422 : 201);
    }

    public function attachmentShow(int $attachment): BinaryFileResponse
    {
        $record = AiAttachment::query()->findOrFail($attachment);
        $this->conversation((int) $record->conversation_id);
        $path = $record->thumbnail_path ?? $record->path;

        abort_if($path === null || ! Storage::disk($record->disk)->exists($path), 404);

        return response()->file(Storage::disk($record->disk)->path($path));
    }

    public function review(StoreAiTestReviewRequest $request, int $conversation): JsonResponse
    {
        $test = $this->conversation($conversation);
        $message = $test->messages()
            ->where('role', 'assistant')
            ->findOrFail((int) $request->validated('message_id'));

        $expectedQuestion = $request->validated('expected_question_id');
        if ($expectedQuestion !== null
            && ! $this->questions->hasQuestion((string) $test->test_family, (string) $expectedQuestion)) {
            throw ValidationException::withMessages([
                'expected_question_id' => 'La pregunta esperada no pertenece a la familia de esta prueba.',
            ]);
        }

        [$expectedProductId, $expectedCode] = $this->validatedExpectedProduct(
            $request->validated('expected_product_id'),
            $request->validated('expected_code'),
        );

        $review = AiTestReview::query()->updateOrCreate(
            [
                'conversation_id' => $test->id,
                'message_id' => $message->id,
                'admin_id' => $this->adminId(),
            ],
            [
                'family' => (string) $test->test_family,
                'rating' => $request->validated('rating'),
                'expected_action' => $request->validated('expected_action'),
                'expected_question_id' => $expectedQuestion,
                'expected_product_id' => $expectedProductId,
                'expected_code' => $expectedCode,
                'surface' => $request->validated('surface'),
                'severity' => $request->validated('severity'),
                'issue_tags' => array_values(array_unique($request->validated('issue_tags', []))),
                'comment' => $request->validated('comment'),
                'question_bank_version' => $this->questions->version(),
                'prompt_version' => $test->prompt_version,
                'provider' => $message->provider,
                'model' => $message->model,
            ],
        );

        return response()->json(['ok' => true, 'review_id' => $review->id]);
    }

    /**
     * La referencia esperada debe existir en el catálogo. Un código puede ser
     * ambiguo porque el legado admite duplicados; un id, en cambio, siempre se
     * normaliza al código canónico de ese producto.
     *
     * @return array{0:?int,1:?string}
     */
    private function validatedExpectedProduct(mixed $productId, mixed $code): array
    {
        $expectedId = is_numeric($productId) ? (int) $productId : null;
        $expectedCode = is_string($code) && trim($code) !== '' ? trim($code) : null;

        if ($expectedId === null && $expectedCode === null) {
            return [null, null];
        }

        if ($expectedId !== null) {
            $product = $this->catalog->find($expectedId);

            if ($product === null) {
                throw ValidationException::withMessages([
                    'expected_product_id' => 'El producto esperado no existe en el catálogo.',
                ]);
            }

            if ($expectedCode !== null && $this->foldCode($expectedCode) !== $this->foldCode($product->code)) {
                throw ValidationException::withMessages([
                    'expected_code' => 'El código esperado no corresponde al producto seleccionado.',
                ]);
            }

            return [$product->id, $product->code];
        }

        $products = $this->catalog->findByCode((string) $expectedCode);
        if ($products === []) {
            $products = $this->catalog->findByNormalizedCode((string) $expectedCode);
        }

        if ($products === []) {
            throw ValidationException::withMessages([
                'expected_code' => 'El código esperado no existe en el catálogo.',
            ]);
        }

        return [null, $products[0]->code];
    }

    private function foldCode(string $code): string
    {
        return mb_strtoupper((string) preg_replace('/[^\pL\pN]+/u', '', trim($code)));
    }

    /** @return array{total:int,correct:int,needs_adjustment:int,incorrect:int,observed_rate:?float} */
    private function reviewMetrics(int $adminId): array
    {
        $counts = AiTestReview::query()
            ->where('admin_id', $adminId)
            ->selectRaw('rating, COUNT(*) as aggregate')
            ->groupBy('rating')
            ->pluck('aggregate', 'rating');

        $correct = (int) ($counts['correct'] ?? 0);
        $needsAdjustment = (int) ($counts['needs_adjustment'] ?? 0);
        $incorrect = (int) ($counts['incorrect'] ?? 0);
        $total = $correct + $needsAdjustment + $incorrect;

        return [
            'total' => $total,
            'correct' => $correct,
            'needs_adjustment' => $needsAdjustment,
            'incorrect' => $incorrect,
            'observed_rate' => $total > 0 ? round(($correct / $total) * 100, 1) : null,
        ];
    }

    private function conversation(int $id): AiConversation
    {
        return AiConversation::query()
            ->testsForAdmin($this->adminId())
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

    private function adminId(): int
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin !== null && $admin->rol === 'administrador', 403);

        return (int) $admin->id;
    }

    private function defaultCustomerId(): ?int
    {
        $value = config('bmh.testing.customer_id');

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    /** @return array{key:string,label:string,questions:list<array<string,mixed>>}|null */
    private function family(string $key): ?array
    {
        foreach ($this->questionBankPayload() as $family) {
            if ($family['key'] === $key) {
                return $family;
            }
        }

        return null;
    }

    /** @return list<array{key:string,label:string,questions:list<array<string,mixed>>}> */
    private function questionBankPayload(): array
    {
        $families = [];

        foreach ($this->questions->families() as $index => $family) {
            if (is_array($family)) {
                $key = (string) ($family['key'] ?? $family['slug'] ?? $family['name'] ?? $index);
                $label = (string) ($family['label'] ?? $family['name'] ?? $key);
            } elseif (is_string($index)) {
                $key = $index;
                $label = (string) $family;
            } else {
                $key = (string) $family;
                $label = Str::title(mb_strtolower($key));
            }

            $families[] = [
                'key' => $key,
                'label' => $label,
                'questions' => $this->normalizedQuestions($key),
            ];
        }

        return $families;
    }

    /** @return list<array<string,mixed>> */
    private function normalizedQuestions(string $family): array
    {
        $questions = [];

        foreach ($this->questions->questionsForFamily($family) as $index => $question) {
            if (is_string($question)) {
                $questions[] = ['id' => $family.'-'.($index + 1), 'text' => $question, 'priority' => $index + 1];

                continue;
            }

            $questions[] = [
                ...$question,
                'id' => (string) ($question['id'] ?? $family.'-'.($index + 1)),
                'text' => (string) ($question['text'] ?? $question['question'] ?? $question['pregunta'] ?? ''),
                'priority' => (int) ($question['priority'] ?? $question['importance'] ?? $question['importancia'] ?? $index + 1),
            ];
        }

        usort($questions, static fn (array $a, array $b): int => $a['priority'] <=> $b['priority']);

        return $questions;
    }

    /** @return array<string,mixed> */
    private function conversationSummary(AiConversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'title' => $conversation->title,
            'status' => $conversation->status,
            'family' => $conversation->test_family,
            'customer_id' => $conversation->customer_id,
        ];
    }
}
