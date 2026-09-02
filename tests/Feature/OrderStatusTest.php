<?php

namespace Tests\Feature;

use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_recording_a_status_updates_the_order_and_logs_history(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $order = Order::factory()->paid()->create();

        $order->recordStatus(Order::STATUS_DELIVERY_ONGOING, 'Handed to courier.', $admin);

        $order->refresh();
        $this->assertSame(Order::STATUS_DELIVERY_ONGOING, $order->status);

        $history = $order->statusHistories()->first();
        $this->assertSame(Order::STATUS_DELIVERY_ONGOING, $history->status);
        $this->assertSame('Handed to courier.', $history->note);
        $this->assertSame($admin->id, $history->changed_by);
    }

    public function test_admin_can_update_order_status_from_the_view_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $order = Order::factory()->paid()->create();

        Livewire::actingAs($admin)
            ->test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('updateStatus', data: [
                'status' => Order::STATUS_DELIVERED,
                'note' => 'Left at the door.',
            ]);

        $this->assertSame(Order::STATUS_DELIVERED, $order->fresh()->status);
    }

    public function test_a_non_admin_cannot_load_the_order_view_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $order = Order::factory()->paid()->create();

        $response = $this->actingAs($user)->get("/admin/orders/{$order->id}");

        $response->assertForbidden();
    }

    public function test_refund_action_marks_payment_refunded_and_order_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $order = Order::factory()->paid()->create();
        Payment::factory()->create([
            'order_id' => $order->id,
            'reference' => $order->payment_reference,
            'gateway' => $order->payment_gateway,
            'status' => Payment::STATUS_SUCCESSFUL,
            'amount_kobo' => $order->total_kobo,
        ]);

        Http::fake([
            'api.paystack.co/*' => Http::response([
                'status' => true,
                'data' => ['id' => 12345, 'status' => 'success', 'amount' => $order->total_kobo, 'currency' => 'NGN'],
            ], 200),
        ]);

        Livewire::actingAs($admin)
            ->test(ViewOrder::class, ['record' => $order->getRouteKey()])
            ->callAction('refund');

        $this->assertSame(Order::STATUS_REJECTED_REFUNDED, $order->fresh()->status);
        $this->assertSame(Payment::STATUS_REFUNDED, $order->fresh()->payments()->first()->status);
    }
}
