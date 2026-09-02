<?php

namespace App\Filament\Widgets;

use App\Models\OrderItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopProductsTable extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Top-selling products';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OrderItem::query()
                    ->selectRaw('min(id) as id, product_name, sum(quantity) as total_quantity, sum(line_total_kobo) as total_revenue_kobo')
                    ->groupBy('product_name')
                    ->orderByDesc('total_quantity')
            )
            ->columns([
                Tables\Columns\TextColumn::make('product_name')->label('Product'),
                Tables\Columns\TextColumn::make('total_quantity')->label('Units sold'),
                Tables\Columns\TextColumn::make('total_revenue_kobo')
                    ->label('Revenue')
                    ->formatStateUsing(fn ($state) => '₦'.number_format($state / 100, 2)),
            ])
            ->paginated([5])
            ->defaultPaginationPageOption(5);
    }
}
