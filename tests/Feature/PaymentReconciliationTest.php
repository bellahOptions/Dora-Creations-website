<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_confirms_a_stale_pending_order_when_a_gateway_reports_success(): void
    {
        $order = Order::factory()->create(['created_at' => now()->subHour()]);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'success', 'amount' => $order->total_kobo, 'currency' => 'NGN'],
            ], 200),
        ]);

        $this->artisan('payments:reconcile')->assertSuccessful();

        $order->refresh();
        $this->assertSame(Order::STATUS_PROCESSING, $order->status);
        $this->assertNotNull($order->paid_at);
    }

    public function test_it_marks_a_stale_pending_order_failed_when_both_gateways_definitively_report_no_payment(): void
    {
        $order = Order::factory()->create(['created_at' => now()->subHour()]);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => false,
                'message' => 'Transaction reference not found',
            ], 200),
            'api.flutterwave.com/v3/transactions/verify_by_reference*' => Http::response([
                'status' => 'error',
                'data' => [],
            ], 200),
        ]);

        $this->artisan('payments:reconcile')->assertSuccessful();

        $order->refresh();
        $this->assertSame(Order::STATUS_PAYMENT_FAILED, $order->status);
        $this->assertNull($order->paid_at);
    }

    public function test_it_leaves_an_order_untouched_when_the_gateway_check_is_inconclusive(): void
    {
        $order = Order::factory()->create(['created_at' => now()->subHour()]);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([], 500),
            'api.flutterwave.com/v3/transactions/verify_by_reference*' => Http::response([], 500),
        ]);

        $this->artisan('payments:reconcile')->assertSuccessful();

        $order->refresh();
        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->status);
    }

    public function test_it_marks_a_very_old_order_failed_even_if_the_gateway_check_stays_inconclusive(): void
    {
        $order = Order::factory()->create(['created_at' => now()->subDays(2)]);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([], 500),
            'api.flutterwave.com/v3/transactions/verify_by_reference*' => Http::response([], 500),
        ]);

        $this->artisan('payments:reconcile')->assertSuccessful();

        $order->refresh();
        $this->assertSame(Order::STATUS_PAYMENT_FAILED, $order->status);
    }

    public function test_it_does_not_check_orders_still_within_the_grace_period(): void
    {
        $order = Order::factory()->create(['created_at' => now()]);

        Http::fake();

        $this->artisan('payments:reconcile')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->fresh()->status);
    }
}
