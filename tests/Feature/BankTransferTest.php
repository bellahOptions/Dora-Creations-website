<?php

namespace Tests\Feature;

use App\Livewire\Checkout\CheckoutPage;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\Payments\PaystackBankResolver;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The direct bank transfer fallback: an admin-configured account whose name is
 * confirmed by Paystack, offered at checkout when card payment isn't wanted.
 */
class BankTransferTest extends TestCase
{
    use RefreshDatabase;

    private function enableBankTransfer(): void
    {
        SiteSetting::current()->update([
            'bank_transfer_enabled' => true,
            'bank_name' => 'GTBank',
            'bank_code' => '058',
            'bank_account_number' => '0123456789',
            'bank_account_name' => 'DORA CREATIONS LTD',
        ]);
    }

    private function cartWith(Product $product, int $quantity = 1, ?int $variantId = null): Cart
    {
        $cart = Cart::create(['cart_token' => (string) Str::uuid()]);

        $cart->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variantId,
            'quantity' => $quantity,
            'unit_price_kobo' => $product->price_kobo,
        ]);

        return $cart;
    }

    public function test_paystack_resolves_an_account_name(): void
    {
        Http::fake([
            'api.paystack.co/bank/resolve*' => Http::response([
                'status' => true,
                'message' => 'Account number resolved',
                'data' => ['account_number' => '0123456789', 'account_name' => 'DORA CREATIONS LTD'],
            ], 200),
        ]);

        $this->assertSame(
            'DORA CREATIONS LTD',
            app(PaystackBankResolver::class)->resolveAccountName('0123456789', '058'),
        );
    }

    public function test_paystack_returns_null_when_the_account_cannot_be_resolved(): void
    {
        Http::fake([
            'api.paystack.co/bank/resolve*' => Http::response([
                'status' => false,
                'message' => 'Could not resolve account name',
            ], 422),
        ]);

        $this->assertNull(app(PaystackBankResolver::class)->resolveAccountName('0000000000', '058'));
    }

    public function test_bank_list_is_keyed_by_paystack_bank_code(): void
    {
        Http::fake([
            'api.paystack.co/bank*' => Http::response([
                'status' => true,
                'data' => [
                    ['code' => '058', 'name' => 'GTBank'],
                    ['code' => '011', 'name' => 'First Bank of Nigeria'],
                ],
            ], 200),
        ]);

        $banks = app(PaystackBankResolver::class)->banks();

        $this->assertSame('GTBank', $banks['058']);
        $this->assertSame('First Bank of Nigeria', $banks['011']);
    }

    public function test_bank_transfer_is_not_offered_until_an_account_is_verified(): void
    {
        $user = User::factory()->create();
        Address::factory()->create(['user_id' => $user->id, 'is_default' => true]);
        $product = Product::factory()->create(['price_kobo' => 100000, 'stock_quantity' => 10]);

        $this->actingAs($user);
        app(CartService::class)->addItem($product, null, 1);

        $component = Livewire::test(CheckoutPage::class);

        $this->assertStringNotContainsString('Bank transfer', $component->html());

        // And a tampered request choosing it is rejected outright.
        $component->set('gateway', Order::GATEWAY_BANK_TRANSFER)
            ->call('placeOrder')
            ->assertHasErrors(['gateway']);

        $this->assertSame(0, Order::count());
    }

    public function test_bank_transfer_is_offered_when_the_admin_has_verified_an_account(): void
    {
        $this->enableBankTransfer();

        $user = User::factory()->create();
        Address::factory()->create(['user_id' => $user->id, 'is_default' => true]);
        $product = Product::factory()->create(['price_kobo' => 100000, 'stock_quantity' => 10]);

        $this->actingAs($user);
        app(CartService::class)->addItem($product, null, 1);

        $html = Livewire::test(CheckoutPage::class)->html();

        $this->assertStringContainsString('Bank transfer', $html);
    }

    public function test_placing_a_bank_transfer_order_skips_the_gateway_and_clears_the_cart(): void
    {
        $this->enableBankTransfer();
        Http::fake();

        $user = User::factory()->create();
        Address::factory()->create(['user_id' => $user->id, 'is_default' => true]);
        $product = Product::factory()->create(['price_kobo' => 1000000, 'stock_quantity' => 10]);

        $this->actingAs($user);
        app(CartService::class)->addItem($product, null, 1);

        $component = Livewire::test(CheckoutPage::class);
        $component->set('gateway', Order::GATEWAY_BANK_TRANSFER)->call('placeOrder')->assertHasNoErrors();

        $order = Order::firstOrFail();

        $this->assertSame(Order::GATEWAY_BANK_TRANSFER, $order->payment_gateway);
        $this->assertTrue($order->isBankTransfer());
        $this->assertTrue($order->awaitingBankTransfer());
        $this->assertFalse($order->isPaid());
        $this->assertSame(0, app(CartService::class)->currentCart()->itemCount());
        $component->assertRedirect(route('order-tracking.show', $order->public_token));

        // No gateway was ever contacted.
        Http::assertNothingSent();
    }

    public function test_the_order_page_shows_the_account_to_transfer_to(): void
    {
        $this->enableBankTransfer();

        $order = app(OrderService::class)->createFromCart(
            $this->cartWith(Product::factory()->create(['price_kobo' => 500000, 'stock_quantity' => 10])),
            ['full_name' => 'Buyer', 'phone' => '+2348012345678', 'state' => 'Lagos', 'city' => 'Lekki', 'line1' => '1 Way'],
            null,
            'buyer@example.com',
            null,
            null,
            Order::GATEWAY_BANK_TRANSFER,
        );

        $this->get(route('order-tracking.show', $order->public_token))
            ->assertOk()
            ->assertSee('GTBank')
            ->assertSee('DORA CREATIONS LTD')
            ->assertSee('0123456789')
            ->assertSee($order->order_number)
            ->assertSee('sent the transfer');
    }

    public function test_customer_saying_they_sent_the_transfer_does_not_mark_the_order_paid(): void
    {
        $this->enableBankTransfer();

        $order = app(OrderService::class)->createFromCart(
            $this->cartWith(Product::factory()->create(['price_kobo' => 500000, 'stock_quantity' => 10])),
            ['full_name' => 'Buyer', 'phone' => '+2348012345678', 'state' => 'Lagos', 'city' => 'Lekki', 'line1' => '1 Way'],
            null,
            'buyer@example.com',
            null,
            null,
            Order::GATEWAY_BANK_TRANSFER,
        );

        // Laravel 13 renamed the CSRF middleware to PreventRequestForgery
        // (ValidateCsrfToken is a deprecated alias), so remove that one.
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        $this->post(route('order-tracking.declare-transfer', $order->public_token))->assertRedirect();

        $order->refresh();

        $this->assertNotNull($order->transfer_declared_at);
        $this->assertFalse($order->isPaid(), 'Only an admin can mark a transfer as received');
        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->status);
    }

    public function test_an_admin_confirming_the_transfer_marks_the_order_paid(): void
    {
        $this->enableBankTransfer();

        $order = app(OrderService::class)->createFromCart(
            $this->cartWith(Product::factory()->create(['price_kobo' => 500000, 'stock_quantity' => 10])),
            ['full_name' => 'Buyer', 'phone' => '+2348012345678', 'state' => 'Lagos', 'city' => 'Lekki', 'line1' => '1 Way'],
            null,
            'buyer@example.com',
            null,
            null,
            Order::GATEWAY_BANK_TRANSFER,
        );

        $admin = User::factory()->create(['is_admin' => true]);
        $payments = app(PaymentService::class);

        $this->assertTrue($payments->markBankTransferReceived($order, $admin, 'Seen in GTBank'));

        $order->refresh();

        $this->assertTrue($order->isPaid());
        $this->assertSame(Order::STATUS_PROCESSING, $order->status);
        $this->assertSame(Order::GATEWAY_BANK_TRANSFER, $order->payment_gateway);
        $this->assertSame($order->order_number, $order->payment_reference);
        $this->assertSame(Payment::STATUS_SUCCESSFUL, $order->payments()->first()->status);
        $this->assertSame($order->total_kobo, $order->payments()->first()->amount_kobo);

        // Confirming twice must not record a second payment.
        $this->assertFalse($payments->markBankTransferReceived($order, $admin));
        $this->assertSame(1, Payment::where('order_id', $order->id)->count());
    }

    public function test_reconciliation_never_fails_a_bank_transfer_order(): void
    {
        $this->enableBankTransfer();

        $order = app(OrderService::class)->createFromCart(
            $this->cartWith(Product::factory()->create(['price_kobo' => 500000, 'stock_quantity' => 10])),
            ['full_name' => 'Buyer', 'phone' => '+2348012345678', 'state' => 'Lagos', 'city' => 'Lekki', 'line1' => '1 Way'],
            null,
            'buyer@example.com',
            null,
            null,
            Order::GATEWAY_BANK_TRANSFER,
        );

        $order->forceFill(['created_at' => now()->subDay()])->save();

        // Both gateways would definitively report "no such transaction".
        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response(['status' => false, 'data' => []], 200),
            'api.flutterwave.com/v3/transactions/verify_by_reference*' => Http::response([
                'status' => 'error',
                'data' => ['status' => 'failed'],
            ], 200),
        ]);

        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->fresh()->status);
        $this->assertFalse($order->fresh()->isPaid());
    }

    public function test_bank_transfer_is_hidden_again_once_the_account_details_are_incomplete(): void
    {
        $this->enableBankTransfer();

        // Account name cleared (e.g. the admin changed the account number), so
        // the option must stop being offered rather than show a wrong name.
        SiteSetting::current()->update(['bank_account_name' => null]);

        $this->assertFalse(SiteSetting::current()->bankTransferIsAvailable());
    }
}



