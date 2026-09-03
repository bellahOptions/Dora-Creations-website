<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentOrdersTable extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Recent orders';

    public function table(Table $table): Table
    {
        return $table
            ->query(Order::query()->latest())
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('Order'),
                Tables\Columns\TextColumn::make('customerName')->label('Customer')->getStateUsing(fn (Order $record) => $record->customerName()),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (Order $record) => $record->statusLabel())
                    ->color(fn (string $state) => match ($state) {
                        Order::STATUS_DELIVERED => 'success',
                        Order::STATUS_REJECTED_REFUNDED, Order::STATUS_PAYMENT_FAILED => 'danger',
                        Order::STATUS_PENDING_PAYMENT => 'gray',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('total_kobo')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => '₦'.number_format($state / 100, 2)),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d M, H:i')->label('Placed'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated(false);
    }
}
