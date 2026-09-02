<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Details')->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (string $context, $state, Forms\Set $set) => $context === 'create' ? $set('slug', Str::slug($state)) : null),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Forms\Components\Select::make('category_id')
                        ->label('Category')
                        ->options(fn () => Category::orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Forms\Components\Textarea::make('description')
                        ->rows(4)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('sku')->label('SKU')->maxLength(255),
                ])->columns(2),

                Forms\Components\Section::make('Pricing & stock')->schema([
                    Forms\Components\TextInput::make('price_kobo')
                        ->label('Price (₦)')
                        ->numeric()
                        ->required()
                        ->prefix('₦')
                        ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                        ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) round($state * 100) : null),
                    Forms\Components\TextInput::make('compare_at_price_kobo')
                        ->label('Compare-at price (₦, optional)')
                        ->numeric()
                        ->prefix('₦')
                        ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                        ->dehydrateStateUsing(fn ($state) => $state !== null && $state !== '' ? (int) round($state * 100) : null),
                    Forms\Components\TextInput::make('stock_quantity')
                        ->label('Stock quantity')
                        ->numeric()
                        ->default(0)
                        ->helperText('Ignored when variants are enabled — each variant tracks its own stock.'),
                    Forms\Components\Toggle::make('has_variants')
                        ->label('This product has size/color variants')
                        ->live(),
                    Forms\Components\Toggle::make('is_published')->default(true),
                    Forms\Components\Toggle::make('is_featured'),
                ])->columns(2),

                Forms\Components\Section::make('Variants')
                    ->visible(fn (Forms\Get $get) => (bool) $get('has_variants'))
                    ->schema([
                        Forms\Components\Repeater::make('variants')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('size')->maxLength(50),
                                Forms\Components\TextInput::make('color')->maxLength(50),
                                Forms\Components\TextInput::make('sku')->label('SKU')->maxLength(255),
                                Forms\Components\TextInput::make('price_kobo')
                                    ->label('Price override (₦, optional)')
                                    ->numeric()
                                    ->prefix('₦')
                                    ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                                    ->dehydrateStateUsing(fn ($state) => $state !== null && $state !== '' ? (int) round($state * 100) : null),
                                Forms\Components\TextInput::make('stock_quantity')->numeric()->default(0)->required(),
                            ])
                            ->columns(5)
                            ->defaultItems(0)
                            ->addActionLabel('Add variant')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Images')->schema([
                    Forms\Components\Repeater::make('images')
                        ->relationship()
                        ->schema([
                            Forms\Components\FileUpload::make('path')
                                ->image()
                                ->maxSize(5120)
                                ->directory('products')
                                ->disk('public')
                                ->required(),
                            Forms\Components\TextInput::make('alt_text')->maxLength(255),
                            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel('Add image')
                        ->columnSpanFull(),
                ]),

                Forms\Components\Section::make('SEO')->schema([
                    Forms\Components\TextInput::make('meta_title')->maxLength(255),
                    Forms\Components\TextInput::make('meta_description')->maxLength(255),
                ])->columns(2)->collapsed(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('images.0.path')->disk('public')->label('Image'),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category.name')->label('Category')->sortable(),
                Tables\Columns\TextColumn::make('price_kobo')
                    ->label('Price')
                    ->formatStateUsing(fn ($state) => '₦'.number_format($state / 100, 2))
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_quantity')->label('Stock')->sortable(),
                Tables\Columns\IconColumn::make('is_published')->boolean()->label('Published'),
                Tables\Columns\IconColumn::make('is_featured')->boolean()->label('Featured'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn () => Category::orderBy('name')->pluck('name', 'id')),
                Tables\Filters\TernaryFilter::make('is_published'),
                Tables\Filters\TernaryFilter::make('is_featured'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
