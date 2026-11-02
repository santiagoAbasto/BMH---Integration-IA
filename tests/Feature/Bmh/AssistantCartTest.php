<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Domain\Customer\Contracts\CustomerRepositoryInterface;
use App\Domain\Pricing\PricingEngine;
use App\Http\Middleware\SincronizarCarrito;
use App\Models\Ai\AiAuditLog;
use App\Models\Ai\AiCartOperation;
use App\Models\Ai\AiConversation;
use App\Models\Producto;
use App\Models\User;
use CodersFree\Shoppingcart\Facades\Cart;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AssistantCartTest extends TestCase
{
    use DatabaseTransactions;

    /** Las dos bases son copias de testing; cada caso revierte todo al terminar. */
    protected $connectionsToTransact = ['mysql_ai'];

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_10_21_000003_create_ai_cart_operations_table.php',
            '--force' => true,
        ])->assertSuccessful();

        // La sincronización persistente tiene su propia suite. Acá se prueba la
        // autorización y el precio del flujo IA con el carrito de sesión.
        $this->withoutMiddleware(SincronizarCarrito::class);

        $row = DB::connection('mysql_legacy')->table('users')
            ->where('habilitado', 1)
            ->where('descuento', '>', 0)
            ->where('rol', 'cliente')
            ->first();

        $this->assertNotNull($row);

        $this->customer = (new User())
            ->setConnection('mysql_legacy')
            ->newQuery()
            ->findOrFail($row->id);

        Cart::destroy();
    }

    protected function tearDown(): void
    {
        Cart::destroy();

        parent::tearDown();
    }

    public function test_no_agrega_una_pieza_hasta_que_el_cliente_la_confirma(): void
    {
        [$conversation, $messageId, $productId] = $this->identifiedProduct();

        $this->actingAs($this->customer)->postJson(
            "/api/assistant/conversations/{$conversation}/cart-items",
            ['message_id' => $messageId, 'product_id' => $productId, 'quantity' => 1],
        )->assertUnprocessable()
            ->assertJsonValidationErrors('product_id');

        $this->assertSame(0, Cart::count());
    }

    public function test_solo_permite_confirmar_un_candidato_del_mensaje(): void
    {
        [$conversation, $messageId, $productId] = $this->identifiedProduct();

        $otherProduct = (int) DB::connection('mysql_legacy')->table('productos')
            ->where('id', '<>', $productId)
            ->value('id');

        $this->actingAs($this->customer)->postJson(
            "/api/assistant/conversations/{$conversation}/feedback",
            ['message_id' => $messageId, 'product_id' => $otherProduct, 'was_correct' => true],
        )->assertUnprocessable()
            ->assertJsonPath('code', 'candidate_mismatch');

        $this->assertNull(AiConversation::query()->findOrFail($conversation)->resolved_product_id);
    }

    public function test_agrega_la_pieza_confirmada_con_precio_recalculado_en_el_servidor(): void
    {
        [$conversation, $messageId, $productId] = $this->identifiedProduct();

        $this->actingAs($this->customer)->postJson(
            "/api/assistant/conversations/{$conversation}/feedback",
            ['message_id' => $messageId, 'product_id' => $productId, 'was_correct' => true],
        )->assertOk()
            ->assertJsonPath('confirmed_product_id', $productId);

        $response = $this->actingAs($this->customer)->postJson(
            "/api/assistant/conversations/{$conversation}/cart-items",
            [
                'message_id' => $messageId,
                'product_id' => $productId,
                'quantity' => 2,
                'price' => 0.01,
            ],
        )->assertCreated()
            ->assertJsonPath('item.product_id', $productId)
            ->assertJsonPath('item.quantity', 2);

        $account = app(CustomerRepositoryInterface::class)->find($this->customer->id);
        $product = app(\App\Domain\Catalog\Contracts\CatalogRepositoryInterface::class)->find($productId);
        $quote = app(PricingEngine::class)->quote($productId, $account, $product?->category?->id);

        $this->assertTrue($quote->isVerified());
        $this->assertSame(round((float) $quote->netPrice, 2), (float) $response->json('item.unit_price'));

        $line = Cart::content()->first(fn ($item): bool => (int) $item->id === $productId);
        $this->assertNotNull($line);
        $this->assertSame((float) $quote->netPrice, (float) $line->price);
        $this->assertSame(2, (int) $line->qty);

        $audit = AiAuditLog::query()
            ->where('conversation_id', $conversation)
            ->where('event', 'cart_item_added')
            ->sole();
        $this->assertSame($productId, $audit->payload['product_id']);
        $this->assertSame(2, $audit->payload['quantity']);
        $this->assertSame('verified_pricing_engine', $audit->payload['price_source']);
    }

    public function test_el_carrito_legacy_tambien_ignora_un_precio_manipulado(): void
    {
        config(['database.default' => 'mysql_legacy']);

        $product = Producto::query()
            ->whereIn('estado', [1, 2])
            ->where('precio', '>', 1)
            ->firstOrFail();

        $this->actingAs($this->customer)->postJson('/carrito/add', [
            'producto_id' => $product->id,
            'qty' => 1,
            'precio' => 0.01,
        ])->assertOk();

        $line = Cart::content()->first(fn ($item): bool => (int) $item->id === (int) $product->id);

        $this->assertNotNull($line);
        $this->assertNotSame(0.01, (float) $line->price);
        $this->assertSame((float) $product->precio_unitario_descontado(), (float) $line->price);
    }

    public function test_un_vendedor_no_agrega_hasta_identificar_al_cliente_comprador(): void
    {
        [$conversation, $messageId, $productId] = $this->identifiedProduct();

        $this->actingAs($this->customer)->postJson(
            "/api/assistant/conversations/{$conversation}/feedback",
            ['message_id' => $messageId, 'product_id' => $productId, 'was_correct' => true],
        )->assertOk();

        $seller = clone $this->customer;
        $seller->setAttribute('rol', 'vendedor');

        $this->actingAs($seller)->postJson(
            "/api/assistant/conversations/{$conversation}/cart-items",
            ['message_id' => $messageId, 'product_id' => $productId, 'quantity' => 1],
        )->assertUnprocessable()
            ->assertJsonPath('code', 'customer_required_for_cart');

        $this->assertSame(0, Cart::count());
    }

    public function test_reintentar_la_misma_confirmacion_no_duplica_el_carrito(): void
    {
        [$conversation, $messageId, $productId] = $this->identifiedProduct();

        $this->actingAs($this->customer)->postJson(
            "/api/assistant/conversations/{$conversation}/feedback",
            ['message_id' => $messageId, 'product_id' => $productId, 'was_correct' => true],
        )->assertOk();

        $payload = ['message_id' => $messageId, 'product_id' => $productId, 'quantity' => 2];

        $this->actingAs($this->customer)
            ->postJson("/api/assistant/conversations/{$conversation}/cart-items", $payload)
            ->assertCreated()
            ->assertJsonPath('idempotent', false);

        $this->actingAs($this->customer)
            ->postJson("/api/assistant/conversations/{$conversation}/cart-items", $payload)
            ->assertCreated()
            ->assertJsonPath('idempotent', true);

        $line = Cart::content()->first(fn ($item): bool => (int) $item->id === $productId);
        $this->assertNotNull($line);
        $this->assertSame(2, (int) $line->qty);
        $this->assertSame(1, AiCartOperation::query()->where('conversation_id', $conversation)->count());
        $this->assertSame(1, AiAuditLog::query()->where('conversation_id', $conversation)->where('event', 'cart_item_added')->count());

        $this->actingAs($this->customer)
            ->getJson("/api/assistant/conversations/{$conversation}")
            ->assertOk()
            ->assertJsonPath('messages.1.candidates.0.product.id', $productId)
            ->assertJsonPath('messages.1.confirmed_product_id', $productId)
            ->assertJsonPath('messages.1.cart_added_product_ids.0', $productId);
    }

    public function test_una_correccion_posterior_revoca_la_confirmacion_del_carrito(): void
    {
        [$conversation, $messageId, $productId] = $this->identifiedProduct();

        $this->actingAs($this->customer)->postJson(
            "/api/assistant/conversations/{$conversation}/feedback",
            ['message_id' => $messageId, 'product_id' => $productId, 'was_correct' => true],
        )->assertOk();

        $this->actingAs($this->customer)->postJson(
            "/api/assistant/conversations/{$conversation}/feedback",
            ['message_id' => $messageId, 'product_id' => $productId, 'was_correct' => false],
        )->assertOk()
            ->assertJsonPath('confirmed_product_id', null);

        $this->actingAs($this->customer)->postJson(
            "/api/assistant/conversations/{$conversation}/cart-items",
            ['message_id' => $messageId, 'product_id' => $productId, 'quantity' => 1],
        )->assertUnprocessable()
            ->assertJsonValidationErrors('product_id');

        $this->assertNull(AiConversation::query()->findOrFail($conversation)->resolved_product_id);
        $this->assertSame(0, Cart::count());
    }

    /** @return array{int,int,int} */
    private function identifiedProduct(): array
    {
        $conversation = (int) $this->actingAs($this->customer)
            ->postJson('/api/assistant/conversations')
            ->assertCreated()
            ->json('conversation.id');

        $result = $this->actingAs($this->customer)->postJson(
            "/api/assistant/conversations/{$conversation}/messages",
            ['message' => '1833'],
        )->assertOk();

        return [
            $conversation,
            (int) $result->json('message.id'),
            (int) $result->json('candidates.0.product.id'),
        ];
    }
}
