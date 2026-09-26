<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\Payments\PaystackGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The buyer's name, email and phone have to reach Paystack with the
 * transaction — the studio needs them to match a payment to a person and a
 * delivery address.
 */
class PaystackCustomerDetailsTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(): Order
    {
        $user = User::factory()->create(['name' => 'Amaka Obi']);
        Address::factory()->create(['user_id' => $user->id, 'is_default' => true]);

        $product = Product::factory()->create(['price_kobo' => 100000, 'stock_quantity' => 5]);

        $this->actingAs($user);
        app(CartService::class)->addItem($product, null, 1);

        return app(OrderService::class)->createFromCart(
            app(CartService::class)->currentCart(),
            [
                'full_name' => 'Amaka Obi',
                'phone' => '+2348012345678',
                'state' => 'Lagos',
                'city' => 'Lekki',
                'line1' => '1 Admiralty Way',
            ],
            $user,
            null,
        );
    }

    public function test_customer_details_are_sent_with_the_transaction(): void
    {
        $order = $this->makeOrder();

        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.com/abc', 'reference' => $order->order_number],
            ], 200),
        ]);

        app(PaystackGateway::class)->initialize($order, 'https://shop.test/checkout/callback/paystack');

        Http::assertSent(function ($request) use ($order) {
            $body = $request->data();
            $metadata = $body['metadata'] ?? [];

            $this->assertSame($order->customerEmail(), $body['email']);
            $this->assertSame('Amaka Obi', $metadata['customer_name'] ?? null);
            $this->assertSame($order->customerEmail(), $metadata['customer_email'] ?? null);
            $this->assertSame('+2348012345678', $metadata['customer_phone'] ?? null);
            $this->assertSame('Amaka', $metadata['first_name'] ?? null);
            $this->assertSame('Obi', $metadata['last_name'] ?? null);

            // Paystack ignores name/phone at the top level, so the details must
            // ride in custom_fields to show on the dashboard receipt.
            $fields = collect($metadata['custom_fields'] ?? [])->pluck('value', 'variable_name');

            $this->assertSame('Amaka Obi', $fields['customer_name'] ?? null);
            $this->assertSame('+2348012345678', $fields['customer_phone'] ?? null);
            $this->assertSame($order->customerEmail(), $fields['customer_email'] ?? null);

            return true;
        });
    }

    public function test_a_guest_order_still_shares_the_details_it_has(): void
    {
        $product = Product::factory()->create(['price_kobo' => 100000, 'stock_quantity' => 5]);
        $cart = Cart::create(['cart_token' => (string) Str::uuid()]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_kobo' => $product->price_kobo,
        ]);

        $order = app(OrderService::class)->createFromCart(
            $cart,
            [
                'full_name' => 'Chidi Guest',
                'phone' => '+2348099999999',
                'state' => 'Lagos',
                'city' => 'Ikeja',
                'line1' => '2 Allen Avenue',
            ],
            null,
            'chidi@example.com',
        );

        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.com/abc', 'reference' => $order->order_number],
            ], 200),
        ]);

        app(PaystackGateway::class)->initialize($order, 'https://shop.test/checkout/callback/paystack');

        Http::assertSent(function ($request) {
            $metadata = $request->data()['metadata'] ?? [];

            $this->assertSame('chidi@example.com', $request->data()['email']);
            $this->assertSame('Chidi Guest', $metadata['customer_name'] ?? null);
            $this->assertSame('+2348099999999', $metadata['customer_phone'] ?? null);

            return true;
        });
    }
}
