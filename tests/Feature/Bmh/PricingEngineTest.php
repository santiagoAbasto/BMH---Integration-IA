<?php

declare(strict_types=1);

namespace Tests\Feature\Bmh;

use App\Domain\Catalog\Contracts\CatalogRepositoryInterface;
use App\Domain\Customer\Contracts\CustomerRepositoryInterface;
use App\Domain\Customer\DTO\CustomerAccount;
use App\Domain\Pricing\DTO\PriceQuote;
use App\Domain\Pricing\PricingEngine;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * El motor de precios.
 *
 * La fórmula no se inventó: se reconstruyó de `Producto::precio_unitario_descontado()`,
 * que es lo que el carrito de producción usa realmente. Estos tests fijan ese
 * comportamiento para que el asesor y el carrito nunca coticen distinto.
 *
 * @see docs/pricing-rules.md
 */
final class PricingEngineTest extends TestCase
{
    private function engine(): PricingEngine
    {
        return app(PricingEngine::class);
    }

    private function customerWithDiscount(float $percent): CustomerAccount
    {
        return new CustomerAccount(
            id: 999999,
            code: 'TEST',
            displayName: 'Cliente de prueba',
            enabled: true,
            discountPercent: $percent,
            resalePercent: null,
            sellerId: null,
        );
    }

    /** Un producto con precio real, para no depender de uno puntual. */
    private function productWithPrice(): object
    {
        $row = DB::connection('mysql_legacy')->table('productos')
            ->where('precio', '>', 100)
            ->whereNotNull('categoria_id')
            ->orderBy('id')
            ->first();

        $this->assertNotNull($row, 'La base legacy debería tener productos con precio.');

        return $row;
    }

    public function test_aplica_el_descuento_del_cliente_sobre_el_precio_de_lista(): void
    {
        $product = $this->productWithPrice();
        $quote   = $this->engine()->quote((int) $product->id, $this->customerWithDiscount(10.0));

        $this->assertTrue($quote->isVerified());
        $this->assertSame(PriceQuote::STATUS_VERIFIED, $quote->status);
        $this->assertEqualsWithDelta((float) $product->precio * 0.9, $quote->netPrice, 0.01);
    }

    public function test_el_precio_de_lista_esta_sin_iva_y_el_iva_se_suma_aparte(): void
    {
        $product = $this->productWithPrice();
        $quote   = $this->engine()->quote((int) $product->id, $this->customerWithDiscount(0.0));

        $this->assertSame(21.0, $quote->taxPercent, 'El IVA vigente en `impuestos` es 21 %.');
        $this->assertEqualsWithDelta((float) $product->precio, $quote->netPrice, 0.01);
        $this->assertEqualsWithDelta((float) $product->precio * 1.21, $quote->priceWithTax, 0.01);
    }

    public function test_un_cliente_sin_acuerdo_paga_precio_de_lista(): void
    {
        $product = $this->productWithPrice();
        $quote   = $this->engine()->quote((int) $product->id, $this->customerWithDiscount(0.0));

        $this->assertSame(0.0, $quote->customerDiscountPercent);
        $this->assertEqualsWithDelta($quote->listPrice, $quote->netPrice, 0.01);
    }

    public function test_no_aplica_los_modificadores_de_categoria_porque_produccion_los_tiene_comentados(): void
    {
        // `categorias.aumento` / `.descuento` existen en la base, pero
        // `Producto::precio()` los tiene comentados. Si el asesor los aplicara,
        // cotizaría distinto que el carrito.
        $this->assertFalse(config('bmh.pricing.apply_category_modifiers'));

        $categoria = DB::connection('mysql_legacy')->table('categorias')
            ->where('descuento', '>', 0)
            ->first();

        if ($categoria === null) {
            $this->markTestSkipped('No hay categorías con descuento cargado.');
        }

        $product = DB::connection('mysql_legacy')->table('productos')
            ->where('categoria_id', $categoria->id)
            ->where('precio', '>', 100)
            ->first();

        if ($product === null) {
            $this->markTestSkipped('La categoría con descuento no tiene productos con precio.');
        }

        $quote = $this->engine()->quote((int) $product->id, $this->customerWithDiscount(0.0), (int) $categoria->id);

        $this->assertSame(0.0, $quote->categoryDiscountPercent);
        $this->assertEqualsWithDelta((float) $product->precio, $quote->netPrice, 0.01);
    }

    public function test_marca_requires_validation_cuando_el_precio_es_el_default_del_esquema(): void
    {
        // 26 productos tienen precio NULL o ≤ 1: ese 1.00 es el DEFAULT de la
        // migración, no un precio real. No se cotiza.
        $product = DB::connection('mysql_legacy')->table('productos')
            ->where('precio', '<=', 1)
            ->first();

        if ($product === null) {
            $this->markTestSkipped('No hay productos con precio por defecto.');
        }

        $quote = $this->engine()->quote((int) $product->id, $this->customerWithDiscount(10.0));

        $this->assertSame(PriceQuote::STATUS_REQUIRES_VALIDATION, $quote->status);
        $this->assertFalse($quote->isVerified());
        $this->assertNull($quote->netPrice);
    }

    public function test_un_precio_no_verificado_no_le_da_ningun_numero_a_la_ia(): void
    {
        $product = DB::connection('mysql_legacy')->table('productos')
            ->where('precio', '<=', 1)
            ->first();

        if ($product === null) {
            $this->markTestSkipped('No hay productos con precio por defecto.');
        }

        $payload = $this->engine()
            ->quote((int) $product->id, $this->customerWithDiscount(10.0))
            ->forAiTool();

        $this->assertArrayNotHasKey('net_price', $payload);
        $this->assertArrayNotHasKey('price_with_tax', $payload);
        $this->assertStringContainsString('No lo comuniques como definitivo', $payload['message']);
    }

    public function test_producto_inexistente_devuelve_unavailable(): void
    {
        $quote = $this->engine()->quote(99999999, $this->customerWithDiscount(10.0));

        $this->assertSame(PriceQuote::STATUS_UNAVAILABLE, $quote->status);
        $this->assertFalse($quote->isVerified());
    }

    public function test_calcula_el_precio_de_reventa_solo_si_el_cliente_lo_tiene_configurado(): void
    {
        $product = $this->productWithPrice();

        $withResale = new CustomerAccount(
            id: 1, code: null, displayName: 'X', enabled: true,
            discountPercent: 10.0, resalePercent: 50.0, sellerId: null,
        );

        $quote = $this->engine()->quote((int) $product->id, $withResale);

        $this->assertNotNull($quote->resalePrice);
        $this->assertEqualsWithDelta($quote->netPrice * 1.5, $quote->resalePrice, 0.01);

        $withoutResale = $this->customerWithDiscount(10.0);
        $this->assertNull($this->engine()->quote((int) $product->id, $withoutResale)->resalePrice);
    }

    public function test_la_bonificacion_por_volumen_se_informa_pero_no_se_aplica_a_una_linea(): void
    {
        // En producción la escala se muestra en el carrito pero
        // `Carrito::total_format()` no la descuenta.
        $this->assertFalse(config('bmh.pricing.apply_bonificacion'));

        $scale = $this->engine()->bonificationScale();

        $this->assertNotEmpty($scale);
        $this->assertSame(0.0, $this->engine()->bonificationFor(1000.0));
        $this->assertGreaterThan(0.0, $this->engine()->bonificationFor(500000.0));

        $product = $this->productWithPrice();
        $quote   = $this->engine()->quote((int) $product->id, $this->customerWithDiscount(0.0));

        $this->assertSame(0.0, $quote->bonificationPercent);
    }

    public function test_el_precio_coincide_con_la_formula_de_produccion(): void
    {
        // Contrato explícito contra Producto::precio_unitario_descontado():
        //   precio × (1 − descuento_producto/100) × (1 − descuento_cliente/100)
        $product = $this->productWithPrice();

        $productDiscount = (float) str_replace(',', '.', (string) ($product->descuento ?? '0'));
        $customerPercent = 14.0;

        $expected = (float) $product->precio
            * (1 - ($productDiscount / 100))
            * (1 - ($customerPercent / 100));

        $quote = $this->engine()->quote((int) $product->id, $this->customerWithDiscount($customerPercent));

        $this->assertEqualsWithDelta(round($expected, 2), $quote->netPrice, 0.01);
    }

    public function test_el_cliente_real_de_la_base_se_mapea_a_su_segmento_comercial(): void
    {
        $row = DB::connection('mysql_legacy')->table('users')
            ->where('descuento', '>', 0)
            ->where('habilitado', 1)
            ->first();

        $this->assertNotNull($row);

        $customer = app(CustomerRepositoryInterface::class)->find((int) $row->id);

        $this->assertNotNull($customer);
        $this->assertSame((float) $row->descuento, $customer->discountPercent);
        $this->assertContains(
            $customer->commercialSegment(),
            ['preferencial', 'mayorista', 'con_acuerdo', 'lista'],
        );
    }

    public function test_el_contexto_que_va_al_llm_no_lleva_pii_ni_el_porcentaje(): void
    {
        $row = DB::connection('mysql_legacy')->table('users')
            ->where('descuento', '>', 0)
            ->whereNotNull('name')
            ->first();

        $customer = app(CustomerRepositoryInterface::class)->find((int) $row->id);
        $context  = $customer->toAiContext();

        $encoded = json_encode($context);

        $this->assertArrayNotHasKey('name', $context);
        $this->assertArrayNotHasKey('discount', $context);
        $this->assertArrayNotHasKey('email', $context);
        $this->assertStringNotContainsString((string) $row->name, (string) $encoded);
        // El porcentaje exacto tampoco viaja: el precio lo calcula el engine.
        $this->assertStringNotContainsString((string) $row->descuento, (string) $encoded);
    }

    public function test_el_catalogo_no_expone_el_hash_de_password(): void
    {
        $row = DB::connection('mysql_legacy')->table('users')->whereNotNull('password')->first();

        $customer = app(CustomerRepositoryInterface::class)->find((int) $row->id);

        $serialized = json_encode(get_object_vars($customer));

        $this->assertStringNotContainsString((string) $row->password, (string) $serialized);
    }

    public function test_el_producto_para_la_ia_no_incluye_precio(): void
    {
        // El precio lo resuelve el PricingEngine y llega por su propia tool.
        // Si viniera dentro del producto, el modelo podría hacer cuentas.
        $product = app(CatalogRepositoryInterface::class)->find((int) $this->productWithPrice()->id);

        $this->assertArrayNotHasKey('price', $product->forAiTool());
        $this->assertArrayNotHasKey('list_price', $product->forAiTool());
    }
}
