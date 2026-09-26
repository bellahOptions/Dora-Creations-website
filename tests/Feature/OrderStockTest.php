<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\OrderService;
use App\Services\Payments\PaystackGateway;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Stock is taken when the order is placed and handed back if it is never
 * paid for — so the shop can't sell the same last item twice, and an
 * abandoned checkout doesn't hold stock hostage forever.
 */
class OrderStockTest extends TestCase
{
    use RefreshDatabase;

    private function placeOrderFor(Product $product, int $quantity, ?ProductVariant $variant = null): Order
    {
        $cart = Cart::create(['cart_token' => (string) Str::uuid()]);

        $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'quantity' => $quantity,
            'unit_price_kobo' => $variant?->priceKobo() ?? $product->price_kobo,
        ]);

        return app(OrderService::class)->createFromCart(
            $cart,
            ['full_name' => 'Buyer', 'phone' => '+2348012345678', 'state' => 'Lagos', 'city' => 'Lekki', 'line1' => '1 Way'],
            null,
            'buyer@example.com',
        );
    }

    public function test_placing_an_order_takes_stock_for_a_simple_product(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 5, 'price_kobo' => 100000]);

        $order = $this->placeOrderFor($product, 2);

        $this->assertSame(3, $product->fresh()->stock_quantity);
        $this->assertNotNull($order->stock_reserved_at);
    }

    public function test_placing_an_order_takes_stock_for_a_variant(): void
    {
        $product = Product::factory()->create(['has_variants' => true, 'stock_quantity' => 0]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Black',
            'stock_quantity' => 2,
        ]);

        $this->placeOrderFor($product, 2, $variant);

        $this->assertSame(0, $variant->fresh()->stock_quantity);
    }

    public function test_an_order_beyond_available_stock_is_refused_and_rolled_back(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 1, 'price_kobo' => 100000]);

        try {
            $this->placeOrderFor($product, 2);
            $this->fail('Expected the order to be refused');
        } catch (InsufficientStockException $e) {
            $this->assertStringContainsString($product->name, $e->userMessage());
        }

        $this->assertSame(0, Order::count(), 'No order should survive a stock failure');
        $this->assertSame(1, $product->fresh()->stock_quantity, 'Stock must be untouched');
    }

    public function test_two_orders_cannot_take_the_last_unit(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 1, 'price_kobo' => 100000]);

        $this->placeOrderFor($product, 1);
        $this->assertSame(0, $product->fresh()->stock_quantity);

        $this->expectException(InsufficientStockException::class);

        $this->placeOrderFor($product, 1);
    }

    public function test_a_failed_payment_hands_the_stock_back(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 5, 'price_kobo' => 100000]);
        $order = $this->placeOrderFor($product, 2);

        $this->assertSame(3, $product->fresh()->stock_quantity);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'failed', 'amount' => 0, 'currency' => 'NGN'],
            ], 200),
        ]);

        app(PaymentService::class)->confirm(app(PaystackGateway::class), $order->order_number);

        $order->refresh();

        $this->assertSame(Order::STATUS_PAYMENT_FAILED, $order->status);
        $this->assertSame(5, $product->fresh()->stock_quantity);
        $this->assertNotNull($order->stock_released_at);
    }

    public function test_stock_is_handed_back_only_once(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 5, 'price_kobo' => 100000]);
        $order = $this->placeOrderFor($product, 2);

        $stocks = app(\App\Services\OrderStockService::class);
        $stocks->release($order);
        $stocks->release($order->fresh());

        $this->assertSame(5, $product->fresh()->stock_quantity, 'Releasing twice must not inflate stock');
    }

    public function test_a_successful_payment_keeps_the_stock_taken(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 5, 'price_kobo' => 100000]);
        $order = $this->placeOrderFor($product, 2);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'success', 'amount' => $order->total_kobo, 'currency' => 'NGN'],
            ], 200),
        ]);

        app(PaymentService::class)->confirm(app(PaystackGateway::class), $order->order_number);

        $order->refresh();

        $this->assertTrue($order->isPaid());
        $this->assertSame(3, $product->fresh()->stock_quantity);
        $this->assertNull($order->stock_released_at);
    }

    public function test_preorder_items_do_not_consume_stock(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 0, 'is_preorder' => true, 'price_kobo' => 100000]);

        $order = $this->placeOrderFor($product, 3);

        $this->assertSame(0, $product->fresh()->stock_quantity);
        $this->assertSame(3, (int) $order->items()->first()->quantity);
    }
}
