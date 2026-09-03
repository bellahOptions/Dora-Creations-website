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
            Forms\Components\Group::make()->columnSpanFull()->schema([
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
                    Forms\Components\Textarea::make('short_description')
                        ->label('Short description')
                        ->helperText('A one- or two-line summary shown on product cards and in search results.')
                        ->rows(2)
                        ->maxLength(300)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('description')
                        ->label('Full description')
                        ->rows(6)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('sku')
                        ->label('SKU')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Generated automatically')
                        ->helperText('Assigned automatically when the product is created.'),
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
                        ->helperText('Ignored when variants are enabled; each variant tracks its own stock.'),
                    Forms\Components\Toggle::make('has_variants')
                        ->label('This product has size/color variants')
                        ->live(),
                    Forms\Components\Toggle::make('is_published')->default(true),
                    Forms\Components\Toggle::make('is_featured'),
                ])->columns(2),

                Forms\Components\Section::make('Pre-order')
                    ->description('Let customers pay in advance for a product that isn\'t in stock yet.')
                    ->schema([
                        Forms\Components\Toggle::make('is_preorder')
                            ->label('This is a pre-order product')
                            ->helperText('When on, customers can buy it even with zero stock.')
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\DatePicker::make('preorder_release_date')
                            ->label('Expected release date (optional)')
                            ->visible(fn (Forms\Get $get) => (bool) $get('is_preorder')),
                        Forms\Components\TextInput::make('preorder_note')
                            ->label('Custom note (optional)')
                            ->placeholder('e.g. Ships in 4–6 weeks')
                            ->helperText('Overrides the release date message when set.')
                            ->maxLength(255)
                            ->visible(fn (Forms\Get $get) => (bool) $get('is_preorder')),
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

                Forms\Components\Section::make('Featured image')
                    ->description('The primary photo used on product cards, the shop grid, and social previews.')
                    ->schema([
                        Forms\Components\FileUpload::make('featured_image_path')
                            ->label('')
                            ->image()
                            ->maxSize(5120)
                            ->directory('products')
                            ->disk(config('filesystems.image_disk')),
                    ]),

                Forms\Components\Section::make('Gallery images')
                    ->description('Additional photos shown in the product page gallery.')
                    ->schema([
                        Forms\Components\Repeater::make('images')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Forms\Components\FileUpload::make('path')
                                    ->image()
                                    ->maxSize(5120)
                                    ->directory('products')
                                    ->disk(config('filesystems.image_disk'))
                                    ->required(),
                                Forms\Components\TextInput::make('alt_text')->maxLength(255),
                                Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Add gallery image')
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
            ->modifyQueryUsing(fn ($query) => $query->with('images'))
            ->columns([
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\ImageColumn::make('featured_image_path')
                        ->disk(config('filesystems.image_disk'))
                        ->label('Image')
                        ->state(fn (Product $record) => $record->featured_image_path ?: $record->images->first()?->path)
                        ->grow(false),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('name')->searchable()->sortable()->weight('bold'),
                        Tables\Columns\TextColumn::make('category.name')->label('Category')->sortable()->color('gray')->size('sm'),
                    ]),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('price_kobo')
                            ->label('Price')
                            ->formatStateUsing(fn ($state) => '₦'.number_format($state / 100, 2))
                            ->sortable(),
                        Tables\Columns\TextColumn::make('stock_quantity')->label('Stock')->sortable()->color('gray')->size('sm'),
                    ])->alignEnd(),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\ToggleColumn::make('is_published')->label('Published'),
                        Tables\Columns\ToggleColumn::make('is_featured')->label('Featured'),
                        Tables\Columns\ToggleColumn::make('is_preorder')->label('Pre-order'),
                    ]),
                ])->from('md'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn () => Category::orderBy('name')->pluck('name', 'id')),
                Tables\Filters\TernaryFilter::make('is_published'),
                Tables\Filters\TernaryFilter::make('is_featured'),
                Tables\Filters\TernaryFilter::make('is_preorder'),
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
