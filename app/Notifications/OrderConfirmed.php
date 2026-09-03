<?php

namespace App\Notifications;

use App\Models\Order;
use App\Services\ReceiptPdfService;
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
        $receipts = app(ReceiptPdfService::class);

        return (new MailMessage)
            ->subject("Order {$this->order->order_number} confirmed, Dora Creations")
            ->markdown('mail.orders.confirmed', ['order' => $this->order])
            ->attachData($receipts->forOrder($this->order)->output(), $receipts->filename($this->order), [
                'mime' => 'application/pdf',
            ]);
    }
}
