<?php

namespace Tests\Feature;

use App\Exceptions\CheckoutException;
use App\Models\Cart;
use App\Models\DiscountCode;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use App\Services\Payments\PaystackGateway;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * A capped discount code must not be reusable across a burst of orders, and
 * an order that never gets paid must hand its claimed use back.
 */
class DiscountCodeTest extends TestCase
{
    use RefreshDatabase;

    private function code(array $attributes = []): DiscountCode
    {
        return DiscountCode::create(array_merge([
            'code' => 'TENOFF',
            'type' => DiscountCode::TYPE_PERCENTAGE,
            'value' => 10,
            'max_uses' => 1,
            'used_count' => 0,
            'is_active' => true,
        ], $attributes));
    }

    private function placeOrder(Product $product, int $quantity = 1, ?string $code = null): Order
    {
        $cart = Cart::create(['cart_token' => (string) Str::uuid()]);

        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price_kobo' => $product->price_kobo,
        ]);

        return app(OrderService::class)->createFromCart(
            $cart,
            ['full_name' => 'Buyer', 'phone' => '+2348012345678', 'state' => 'Lagos', 'city' => 'Lekki', 'line1' => '1 Way'],
            null,
            'buyer@example.com',
            null,
            $code,
        );
    }

    public function test_a_discount_code_reduces_the_order_total(): void
    {
        $this->code(['max_uses' => null]);
        $product = Product::factory()->create(['price_kobo' => 100000, 'stock_quantity' => 5]);

        $order = $this->placeOrder($product, 1, 'TENOFF');

        // 1000 naira item, 10% off, plus the 2500 naira flat shipping.
        $this->assertSame(10000, $order->discount_kobo);
        $this->assertSame(100000 + 250000 - 10000, $order->total_kobo);
    }

    public function test_a_single_use_code_cannot_be_claimed_by_a_second_order(): void
    {
        $code = $this->code(['max_uses' => 1]);
        $product = Product::factory()->create(['price_kobo' => 100000, 'stock_quantity' => 10]);

        $this->placeOrder($product, 1, 'TENOFF');
        $this->assertSame(1, $code->fresh()->used_count);

        try {
            $this->placeOrder($product, 1, 'TENOFF');
            $this->fail('The exhausted code should have been refused');
        } catch (CheckoutException $e) {
            $this->assertStringContainsString('discount code', strtolower($e->userMessage()));
        }

        $this->assertSame(1, Order::count(), 'The refused order must not be created');
        $this->assertSame(1, $code->fresh()->used_count, 'The cap must not be exceeded');
    }

    /**
     * The atomic claim is the backstop for two orders racing past the
     * read-only cap check at the same instant.
     */
    public function test_reserving_a_use_is_atomic_and_refuses_once_exhausted(): void
    {
        $code = $this->code(['max_uses' => 2]);

        $this->assertTrue($code->reserveUsage());
        $this->assertTrue($code->reserveUsage());
        $this->assertFalse($code->reserveUsage(), 'The third claim must fail');
        $this->assertSame(2, $code->fresh()->used_count);

        $code->releaseUsage();
        $this->assertTrue($code->reserveUsage());
        $this->assertSame(2, $code->fresh()->used_count);
    }

    public function test_an_unlimited_code_never_exhausts(): void
    {
        $code = $this->code(['max_uses' => null]);

        foreach (range(1, 5) as $ignored) {
            $this->assertTrue($code->reserveUsage());
        }

        $this->assertSame(5, $code->fresh()->used_count);
    }

    public function test_a_failed_payment_hands_the_discount_use_back(): void
    {
        $code = $this->code(['max_uses' => 1]);
        $product = Product::factory()->create(['price_kobo' => 100000, 'stock_quantity' => 5]);

        $order = $this->placeOrder($product, 1, 'TENOFF');
        $this->assertSame(1, $code->fresh()->used_count);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'failed', 'amount' => 0, 'currency' => 'NGN'],
            ], 200),
        ]);

        app(PaymentService::class)->confirm(app(PaystackGateway::class), $order->order_number);

        $this->assertSame(Order::STATUS_PAYMENT_FAILED, $order->fresh()->status);
        $this->assertSame(0, $code->fresh()->used_count, 'A code on an unpaid order must be reusable');
    }

    public function test_a_successful_payment_keeps_the_discount_use(): void
    {
        $code = $this->code(['max_uses' => 1]);
        $product = Product::factory()->create(['price_kobo' => 100000, 'stock_quantity' => 5]);

        $order = $this->placeOrder($product, 1, 'TENOFF');

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'success', 'amount' => $order->total_kobo, 'currency' => 'NGN'],
            ], 200),
        ]);

        app(PaymentService::class)->confirm(app(PaystackGateway::class), $order->order_number);

        $this->assertTrue($order->fresh()->isPaid());
        $this->assertSame(1, $code->fresh()->used_count);
    }

    public function test_a_non_ngn_settlement_is_never_accepted(): void
    {
        $product = Product::factory()->create(['price_kobo' => 100000, 'stock_quantity' => 5]);
        $order = $this->placeOrder($product, 1);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'success', 'amount' => $order->total_kobo, 'currency' => 'USD'],
            ], 200),
        ]);

        $result = app(PaymentService::class)->confirm(app(PaystackGateway::class), $order->order_number);

        $this->assertFalse($result->fresh()->isPaid(), 'A different currency must not settle an NGN order');
    }
}
