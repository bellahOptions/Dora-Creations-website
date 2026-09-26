<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the shop owner about a bank-transfer order: once when it is placed
 * (so they know money is coming) and again when the customer says they have
 * sent it (so they know to go and check the bank).
 */
class BankTransferUpdate extends Notification implements ShouldQueue
{
    use Queueable;

    public const PHASE_PLACED = 'placed';

    public const PHASE_DECLARED = 'declared';

    public function __construct(
        public Order $order,
        public string $phase,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->phase === self::PHASE_DECLARED
            ? "Transfer sent: {$this->order->order_number}, check the bank"
            : "Bank transfer order: {$this->order->order_number} ({$this->order->formattedTotal()})";

        return (new MailMessage)
            ->subject($subject)
            ->markdown('mail.admin.bank-transfer-update', [
                'order' => $this->order,
                'phase' => $this->phase,
            ]);
    }
}
