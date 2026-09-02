<?php

namespace Tests\Feature;

use App\Livewire\Checkout\CheckoutPage;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\Payments\PaystackGateway;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logged_in_user_can_place_an_order_and_is_redirected_to_the_gateway(): void
    {
        $user = User::factory()->create();
        Address::factory()->create(['user_id' => $user->id, 'is_default' => true]);
        $product = Product::factory()->create(['price_kobo' => 1000000]);

        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/abc123',
                    'reference' => 'will-be-overridden',
                ],
            ], 200),
        ]);

        $this->actingAs($user);
        app(CartService::class)->addItem($product, null, 1);

        Livewire::actingAs($user)
            ->test(CheckoutPage::class)
            ->set('gateway', 'paystack')
            ->call('placeOrder')
            ->assertHasNoErrors()
            ->assertRedirect('https://checkout.paystack.com/abc123');

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertSame($user->id, $order->user_id);
        $this->assertSame(1000000 + 250000, $order->total_kobo);
        $this->assertSame(0, app(CartService::class)->currentCart()->itemCount());
    }

    /**
     * Guest checkout's own order-creation path (no account, manual address,
     * guest_email) is covered directly against OrderService below — see
     * CartTest for CartService's guest cookie-cart behaviour.
     */
    public function test_guest_order_creation_records_guest_email_and_no_user(): void
    {
        $product = Product::factory()->create(['price_kobo' => 1000000]);
        $cart = Cart::create(['cart_token' => (string) Str::uuid()]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_kobo' => $product->price_kobo,
        ]);

        $order = app(OrderService::class)->createFromCart(
            $cart,
            [
                'full_name' => 'Guest Buyer',
                'phone' => '+2348012345678',
                'state' => 'Lagos',
                'city' => 'Lekki',
                'line1' => '1 Admiralty Way',
            ],
            null,
            'guest@example.com',
        );

        $this->assertNull($order->user_id);
        $this->assertSame('guest@example.com', $order->guest_email);
        $this->assertSame(1000000 + 250000, $order->total_kobo);
    }

    public function test_a_successful_paystack_verification_marks_the_order_paid(): void
    {
        $product = Product::factory()->create(['price_kobo' => 500000]);
        $cartService = app(CartService::class);
        $cartService->addItem($product, null, 1);

        $order = app(OrderService::class)->createFromCart(
            $cartService->currentCart(),
            [
                'full_name' => 'Guest Buyer',
                'phone' => '+2348012345678',
                'state' => 'Lagos',
                'city' => 'Lekki',
                'line1' => '1 Admiralty Way',
            ],
            null,
            'guest@example.com',
        );

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'amount' => $order->total_kobo,
                    'currency' => 'NGN',
                ],
            ], 200),
        ]);

        $confirmed = app(PaymentService::class)->confirm(app(PaystackGateway::class), $order->order_number);

        $this->assertSame(Order::STATUS_PROCESSING, $confirmed->status);
        $this->assertNotNull($confirmed->paid_at);
        $this->assertSame(Payment::STATUS_SUCCESSFUL, $confirmed->payments()->first()->status);
    }

    public function test_an_amount_mismatch_does_not_mark_the_order_paid(): void
    {
        $product = Product::factory()->create(['price_kobo' => 500000]);
        $cartService = app(CartService::class);
        $cartService->addItem($product, null, 1);

        $order = app(OrderService::class)->createFromCart(
            $cartService->currentCart(),
            [
                'full_name' => 'Guest Buyer',
                'phone' => '+2348012345678',
                'state' => 'Lagos',
                'city' => 'Lekki',
                'line1' => '1 Admiralty Way',
            ],
            null,
            'guest@example.com',
        );

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'amount' => 1,
                    'currency' => 'NGN',
                ],
            ], 200),
        ]);

        $result = app(PaymentService::class)->confirm(app(PaystackGateway::class), $order->order_number);

        $this->assertFalse($result->isPaid());
        $this->assertSame(Payment::STATUS_FAILED, $result->payments()->first()->status);
    }

    public function test_confirming_an_already_paid_order_is_idempotent(): void
    {
        $product = Product::factory()->create(['price_kobo' => 500000]);
        $cartService = app(CartService::class);
        $cartService->addItem($product, null, 1);

        $order = app(OrderService::class)->createFromCart(
            $cartService->currentCart(),
            [
                'full_name' => 'Guest Buyer',
                'phone' => '+2348012345678',
                'state' => 'Lagos',
                'city' => 'Lekki',
                'line1' => '1 Admiralty Way',
            ],
            null,
            'guest@example.com',
        );

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'success', 'amount' => $order->total_kobo, 'currency' => 'NGN'],
            ], 200),
        ]);

        $gateway = app(PaystackGateway::class);
        $service = app(PaymentService::class);

        $service->confirm($gateway, $order->order_number);
        $service->confirm($gateway, $order->order_number);

        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
        $this->assertSame(1, $order->fresh()->statusHistories()->where('status', Order::STATUS_PROCESSING)->count());
    }
}
