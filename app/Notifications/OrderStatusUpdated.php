<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject())
            ->markdown('mail.orders.status-updated', ['order' => $this->order]);
    }

    protected function subject(): string
    {
        $number = $this->order->order_number;

        return match ($this->order->status) {
            Order::STATUS_DELIVERY_ONGOING => "Order {$number} is on its way",
            Order::STATUS_DELIVERED => "Order {$number} has been delivered",
            Order::STATUS_REJECTED_REFUNDED => "Order {$number} was rejected/refunded",
            Order::STATUS_PAYMENT_FAILED => "We couldn't confirm payment for order {$number}",
            default => "Update on order {$number}",
        };
    }
}
