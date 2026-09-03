<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('order_number')->label('Order')->searchable()->sortable()->weight('bold'),
                        Tables\Columns\TextColumn::make('customerName')->label('Customer')->getStateUsing(fn (Order $record) => $record->customerName())->color('gray')->size('sm'),
                        Tables\Columns\TextColumn::make('customerEmail')->label('Email')->getStateUsing(fn (Order $record) => $record->customerEmail())->toggleable()->color('gray')->size('sm'),
                    ]),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('status')
                            ->badge()
                            ->formatStateUsing(fn (Order $record) => $record->statusLabel())
                            ->color(fn (string $state) => match ($state) {
                                Order::STATUS_DELIVERED => 'success',
                                Order::STATUS_REJECTED_REFUNDED, Order::STATUS_PAYMENT_FAILED => 'danger',
                                Order::STATUS_PENDING_PAYMENT => 'gray',
                                default => 'warning',
                            }),
                        Tables\Columns\TextColumn::make('payment_gateway')->formatStateUsing(fn ($state) => $state ? Str::headline($state) : '—')->color('gray')->size('sm'),
                    ]),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('total_kobo')
                            ->label('Total')
                            ->formatStateUsing(fn ($state) => '₦'.number_format($state / 100, 2))
                            ->sortable()
                            ->weight('bold'),
                        Tables\Columns\TextColumn::make('created_at')->label('Placed')->dateTime('d M Y, H:i')->sortable()->color('gray')->size('sm'),
                    ])->alignEnd(),
                ])->from('md'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    Order::STATUS_PENDING_PAYMENT => 'Pending payment',
                    Order::STATUS_PAYMENT_FAILED => 'Payment failed',
                    Order::STATUS_PROCESSING => 'Processing',
                    Order::STATUS_DELIVERY_ONGOING => 'Delivery ongoing',
                    Order::STATUS_DELIVERED => 'Delivered',
                    Order::STATUS_REJECTED_REFUNDED => 'Rejected / Refunded',
                    Order::STATUS_REVIEW_REQUESTED => 'Review requested',
                ]),
                Tables\Filters\SelectFilter::make('payment_gateway')->options([
                    'paystack' => 'Paystack',
                    'flutterwave' => 'Flutterwave',
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::whereIn('status', [Order::STATUS_PROCESSING])->count();
    }
}
