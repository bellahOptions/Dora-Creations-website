<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\Wishlist as WishlistModel;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class Wishlists extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Wishlists';

    protected static string $view = 'filament.pages.wishlists';

    public function getTopWishlistedProducts(): Collection
    {
        return Product::query()
            ->withCount('wishlistedBy')
            ->whereHas('wishlistedBy')
            ->orderByDesc('wishlisted_by_count')
            ->limit(10)
            ->get();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(WishlistModel::query()->with(['user', 'product']))
            ->columns([
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('user.name')->label('Customer')->weight('bold'),
                        Tables\Columns\TextColumn::make('user.email')->label('Email')->color('gray')->size('sm'),
                    ]),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('product.name')->label('Product'),
                        Tables\Columns\TextColumn::make('product.category.name')->label('Category')->color('gray')->size('sm'),
                    ]),
                    Tables\Columns\TextColumn::make('created_at')->label('Saved')->dateTime('d M Y, H:i')->sortable()->color('gray')->size('sm'),
                ])->from('md'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable(),
            ]);
    }
}
