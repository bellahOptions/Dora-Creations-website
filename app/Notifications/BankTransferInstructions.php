<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\SiteSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sends the customer the account to pay into, so a bank-transfer order can
 * still be completed if they close the tab before paying.
 */
class BankTransferInstructions extends Notification implements ShouldQueue
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
            ->subject("Order {$this->order->order_number}: complete your bank transfer")
            ->markdown('mail.orders.bank-transfer-instructions', [
                'order' => $this->order,
                'settings' => SiteSetting::current(),
                'trackingUrl' => route('order-tracking.show', $this->order->public_token),
            ]);
    }
}
