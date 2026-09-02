<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmed extends Notification implements ShouldQueue
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
            ->subject("Order {$this->order->order_number} confirmed — Dora Creations")
            ->greeting('Thank you for your order!')
            ->line("We've received payment for order {$this->order->order_number}.")
            ->line('Total: '.$this->order->formattedTotal())
            ->action('Track your order', route('order-tracking.show', $this->order->public_token))
            ->line('We\'ll let you know as soon as it ships.');
    }
}
