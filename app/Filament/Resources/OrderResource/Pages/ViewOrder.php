<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Payments\PaymentGatewayManager;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components as Infolist;
use Filament\Infolists\Infolist as InfolistLayout;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('updateStatus')
                ->label('Update status')
                ->icon('heroicon-o-arrow-path')
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('New status')
                        ->options([
                            Order::STATUS_PROCESSING => 'Processing',
                            Order::STATUS_DELIVERY_ONGOING => 'Delivery ongoing',
                            Order::STATUS_DELIVERED => 'Delivered',
                            Order::STATUS_REJECTED_REFUNDED => 'Rejected / Refunded',
                            Order::STATUS_REVIEW_REQUESTED => 'Review requested',
                        ])
                        ->default(fn () => $this->record->status)
                        ->required(),
                    Forms\Components\Textarea::make('note')
                        ->label('Note (optional, visible in the order history)')
                        ->rows(2),
                ])
                ->action(function (array $data): void {
                    $this->record->recordStatus($data['status'], $data['note'] ?: null, auth()->user());

                    Notification::make()
                        ->title('Order status updated')
                        ->success()
                        ->send();

                    $this->record->refresh();
                })
                ->visible(fn () => $this->record->isPaid()),

            Actions\Action::make('refund')
                ->label('Refund')
                ->icon('heroicon-o-receipt-refund')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('This will attempt to refund the customer via the original payment gateway and mark the order as rejected/refunded.')
                ->action(function (PaymentGatewayManager $gateways): void {
                    $gateway = $gateways->get($this->record->payment_gateway);
                    $refunded = $gateway->refund($this->record->payment_reference);

                    if (! $refunded) {
                        Notification::make()
                            ->title('Refund request failed')
                            ->body('The gateway did not confirm the refund. No status change was made.')
                            ->danger()
                            ->send();

                        return;
                    }

                    Payment::where('order_id', $this->record->id)
                        ->where('status', Payment::STATUS_SUCCESSFUL)
                        ->update(['status' => Payment::STATUS_REFUNDED]);

                    $this->record->recordStatus(Order::STATUS_REJECTED_REFUNDED, 'Refunded via admin.', auth()->user());

                    Notification::make()
                        ->title('Order refunded')
                        ->success()
                        ->send();

                    $this->record->refresh();
                })
                ->visible(fn () => $this->record->isPaid() && $this->record->status !== Order::STATUS_REJECTED_REFUNDED),
        ];
    }

    public function infolist(InfolistLayout $infolist): InfolistLayout
    {
        return $infolist->schema([
            Infolist\Section::make('Order')
                ->schema([
                    Infolist\TextEntry::make('order_number')->label('Order number'),
                    Infolist\TextEntry::make('status')->badge()->formatStateUsing(fn (Order $record) => $record->statusLabel())
                        ->color(fn (string $state) => match ($state) {
                            Order::STATUS_DELIVERED => 'success',
                            Order::STATUS_REJECTED_REFUNDED => 'danger',
                            Order::STATUS_PENDING_PAYMENT => 'gray',
                            default => 'warning',
                        }),
                    Infolist\TextEntry::make('created_at')->label('Placed')->dateTime('d M Y, H:i'),
                    Infolist\TextEntry::make('paid_at')->label('Paid')->dateTime('d M Y, H:i')->placeholder('Not paid'),
                    Infolist\TextEntry::make('payment_gateway')->formatStateUsing(fn ($state) => $state ? Str::headline($state) : '—'),
                    Infolist\TextEntry::make('payment_reference')->label('Reference')->placeholder('—'),
                ])->columns(3),

            Infolist\Section::make('Customer & delivery')
                ->schema([
                    Infolist\TextEntry::make('customerName')->label('Customer')->getStateUsing(fn (Order $record) => $record->customerName()),
                    Infolist\TextEntry::make('customerEmail')->label('Email')->getStateUsing(fn (Order $record) => $record->customerEmail()),
                    Infolist\TextEntry::make('shipping_phone')->label('Phone'),
                    Infolist\TextEntry::make('shippingAddress')->label('Address')->columnSpanFull()
                        ->getStateUsing(fn (Order $record) => collect([
                            $record->shipping_line1, $record->shipping_line2, $record->shipping_city, $record->shipping_state, $record->shipping_country,
                        ])->filter()->implode(', ')),
                    Infolist\TextEntry::make('customer_note')->label('Customer note')->placeholder('—')->columnSpanFull(),
                ])->columns(3),

            Infolist\Section::make('Items')
                ->schema([
                    Infolist\RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->schema([
                            Infolist\TextEntry::make('product_name')->label('Product'),
                            Infolist\TextEntry::make('variant_label')->label('Variant')->placeholder('—'),
                            Infolist\TextEntry::make('quantity'),
                            Infolist\TextEntry::make('unit_price_kobo')->label('Unit price')->formatStateUsing(fn ($state) => '₦'.number_format($state / 100, 2)),
                            Infolist\TextEntry::make('line_total_kobo')->label('Line total')->formatStateUsing(fn ($state) => '₦'.number_format($state / 100, 2)),
                        ])
                        ->columns(5),
                    Infolist\TextEntry::make('subtotal_kobo')->label('Subtotal')->formatStateUsing(fn ($state) => '₦'.number_format($state / 100, 2)),
                    Infolist\TextEntry::make('shipping_kobo')->label('Shipping')->formatStateUsing(fn ($state) => $state === 0 ? 'Free' : '₦'.number_format($state / 100, 2)),
                    Infolist\TextEntry::make('total_kobo')->label('Total')->formatStateUsing(fn ($state) => '₦'.number_format($state / 100, 2))->weight('bold'),
                ])->columns(3),

            Infolist\Section::make('Status history')
                ->schema([
                    Infolist\RepeatableEntry::make('statusHistories')
                        ->hiddenLabel()
                        ->schema([
                            Infolist\TextEntry::make('status')->formatStateUsing(fn ($state) => Str::headline($state)),
                            Infolist\TextEntry::make('note')->placeholder('—'),
                            Infolist\TextEntry::make('changedBy.name')->label('By')->placeholder('System'),
                            Infolist\TextEntry::make('created_at')->dateTime('d M Y, H:i'),
                        ])
                        ->columns(4),
                ]),
        ]);
    }
}
