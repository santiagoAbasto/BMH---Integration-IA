<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Domain\Catalog\Contracts\CatalogRepositoryInterface;
use App\Domain\Customer\DTO\CustomerAccount;
use App\Domain\Pricing\PricingEngine;
use App\Models\Ai\AiAuditLog;
use App\Models\Ai\AiCartOperation;
use App\Models\Ai\AiConversation;
use App\Models\Ai\AiFeedback;
use CodersFree\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Agrega al carrito una pieza que el cliente confirmó en el asesor.
 *
 * El navegador y el modelo sólo envían ids y cantidad. El producto se vuelve
 * a cargar del catálogo y el precio se recalcula en el servidor, en el mismo
 * momento en que se crea el renglón.
 */
final class AssistantCartService
{
    public function __construct(
        private readonly CatalogRepositoryInterface $catalog,
        private readonly PricingEngine $pricing,
    ) {
    }

    /** @return array<string, mixed> */
    public function add(
        AiConversation $conversation,
        CustomerAccount $customer,
        int $messageId,
        int $productId,
        int $quantity,
    ): array {
        if (! $customer->enabled) {
            throw ValidationException::withMessages([
                'product_id' => 'La cuenta todavía no está habilitada para armar pedidos.',
            ]);
        }

        $decision = AiFeedback::query()
            ->where('conversation_id', $conversation->id)
            ->where('message_id', $messageId)
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->first();

        if ($decision === null || ! $decision->was_correct || (int) $decision->product_id !== $productId) {
            throw ValidationException::withMessages([
                'product_id' => 'Confirmá primero que es la pieza correcta antes de agregarla.',
            ]);
        }

        $product = $this->catalog->find($productId);

        if ($product === null || ! $product->condition->isPublic()) {
            throw ValidationException::withMessages([
                'product_id' => 'La pieza ya no está disponible en el catálogo publicado.',
            ]);
        }

        $quote = $this->pricing->quote($productId, $customer, $product->category?->id);

        if (! $quote->isVerified()) {
            throw ValidationException::withMessages([
                'product_id' => 'El precio necesita validación de un asesor antes de agregar la pieza.',
            ]);
        }

        $operation = AiCartOperation::query()->firstOrCreate(
            ['feedback_id' => $decision->id],
            [
                'conversation_id' => $conversation->id,
                'message_id' => $messageId,
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'status' => 'pending',
            ],
        );

        if (! $operation->wasRecentlyCreated) {
            if ($operation->status === 'completed' && is_array($operation->result)) {
                return [...$operation->result, 'idempotent' => true];
            }

            throw ValidationException::withMessages([
                'product_id' => 'La pieza ya se está agregando. Esperá un instante antes de reintentar.',
            ]);
        }

        try {
            $line = Cart::add($product->id, $product->name, $quantity, (float) $quote->netPrice);

            $result = [
                'ok' => true,
                'item' => [
                    'row_id' => $line->rowId,
                    'product_id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name,
                    'quantity' => $line->qty,
                    'unit_price' => round((float) $quote->netPrice, 2),
                    'currency' => $quote->currency,
                ],
                'cart_count' => Cart::count(),
                'cart_url' => route('carrito'),
                'availability_note' => 'La disponibilidad final se confirma al procesar el pedido.',
                'idempotent' => false,
            ];

            $operation->update(['status' => 'completed', 'result' => $result]);
        } catch (\Throwable $exception) {
            $operation->delete();

            throw $exception;
        }

        try {
            AiAuditLog::query()->create([
                'conversation_id' => $conversation->id,
                'customer_id' => $customer->id,
                'event' => 'cart_item_added',
                'prompt_version' => $conversation->prompt_version,
                'payload' => [
                    'operation_id' => $operation->id,
                    'message_id' => $messageId,
                    'product_id' => $product->id,
                    'product_code' => $product->code,
                    'quantity' => $quantity,
                    'price_source' => $quote->calculationSource,
                ],
            ]);
        } catch (\Throwable $exception) {
            Log::warning('bmh.ai.cart.audit_failed', [
                'operation_id' => $operation->id,
                'exception' => $exception::class,
            ]);
        }

        return $result;
    }
}
