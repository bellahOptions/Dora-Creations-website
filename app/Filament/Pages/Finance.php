<?php

namespace App\Filament\Pages;

use App\Models\Payment;
use Filament\Actions;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Finance extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Finance';

    protected static string $view = 'filament.pages.finance';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn () => $this->exportCsv()),
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $payments = Payment::with('order')->latest()->get();

        return Response::streamDownload(function () use ($payments) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Order', 'Gateway', 'Reference', 'Status', 'Amount (NGN)']);

            foreach ($payments as $payment) {
                fputcsv($handle, [
                    $payment->created_at->format('Y-m-d H:i'),
                    $payment->order?->order_number,
                    Str::headline($payment->gateway),
                    $payment->reference,
                    Str::headline($payment->status),
                    number_format($payment->amount_kobo / 100, 2),
                ]);
            }

            fclose($handle);
        }, 'dora-creations-payments-'.now()->format('Y-m-d').'.csv');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Payment::query()->with('order')->latest())
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime('d M Y, H:i')->sortable(),
                Tables\Columns\TextColumn::make('order.order_number')->label('Order'),
                Tables\Columns\TextColumn::make('gateway')->formatStateUsing(fn ($state) => Str::headline($state)),
                Tables\Columns\TextColumn::make('reference')->copyable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        Payment::STATUS_SUCCESSFUL => 'success',
                        Payment::STATUS_REFUNDED => 'gray',
                        Payment::STATUS_FAILED => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('amount_kobo')
                    ->label('Amount')
                    ->formatStateUsing(fn ($state) => '₦'.number_format($state / 100, 2))
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('gateway')->options([
                    'paystack' => 'Paystack',
                    'flutterwave' => 'Flutterwave',
                ]),
                Tables\Filters\SelectFilter::make('status')->options([
                    Payment::STATUS_SUCCESSFUL => 'Successful',
                    Payment::STATUS_FAILED => 'Failed',
                    Payment::STATUS_REFUNDED => 'Refunded',
                    Payment::STATUS_INITIATED => 'Initiated',
                ]),
            ]);
    }

    public function getRevenueStats(): array
    {
        $successful = Payment::where('status', Payment::STATUS_SUCCESSFUL);

        $totalRevenueKobo = (clone $successful)->sum('amount_kobo');
        $refundedKobo = Payment::where('status', Payment::STATUS_REFUNDED)->sum('amount_kobo');
        $paystackKobo = (clone $successful)->where('gateway', 'paystack')->sum('amount_kobo');
        $flutterwaveKobo = (clone $successful)->where('gateway', 'flutterwave')->sum('amount_kobo');

        return [
            'total' => $totalRevenueKobo,
            'refunded' => $refundedKobo,
            'net' => $totalRevenueKobo - $refundedKobo,
            'paystack' => $paystackKobo,
            'flutterwave' => $flutterwaveKobo,
        ];
    }
}
