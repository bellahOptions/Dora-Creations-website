<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Support\VerifiedUpload;
use App\Models\Category;
use App\Models\Order;
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
                        ->afterStateUpdated(function (string $context, ?string $state, Forms\Get $get, Forms\Set $set) {
                            if ($context === 'create') {
                                $set('slug', Str::slug($state));
                            }

                            if (blank($get('meta_title'))) {
                                $set('meta_title', $state);
                            }
                        }),
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
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, Forms\Get $get, Forms\Set $set) {
                            if (blank($get('meta_description'))) {
                                $set('meta_description', Str::limit((string) $state, 160));
                            }
                        })
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
                        ->minValue(0)
                        ->default(0)
                        ->required()
                        ->disabled(fn (Forms\Get $get) => (bool) $get('has_variants'))
                        ->dehydrateStateUsing(fn ($state) => $state === null || $state === '' ? 0 : (int) $state)
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
                    ->description('Add the options customers choose from on the product page, such as size and color. Use the generator for every combination, or add variants one by one. You can also leave this empty for now — the product stays on sale and you confirm each buyer\'s size/colour afterwards.')
                    ->schema([
                        Forms\Components\Fieldset::make('Generate combinations')->schema([
                            Forms\Components\TagsInput::make('generate_sizes')
                                ->label('Sizes')
                                ->placeholder('e.g. S, M, L (press Enter after each)')
                                ->dehydrated(false),
                            Forms\Components\TagsInput::make('generate_colors')
                                ->label('Colors')
                                ->placeholder('e.g. Black, Red (press Enter after each)')
                                ->dehydrated(false),
                            Forms\Components\TextInput::make('generate_stock')
                                ->label('Stock for each new variant')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->dehydrated(false),
                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('generateVariants')
                                    ->label('Generate variants')
                                    ->icon('heroicon-o-sparkles')
                                    ->action(function (Forms\Get $get, Forms\Set $set) {
                                        $sizes = array_values(array_filter(array_map('trim', (array) $get('generate_sizes')))) ?: [null];
                                        $colors = array_values(array_filter(array_map('trim', (array) $get('generate_colors')))) ?: [null];

                                        if ($sizes === [null] && $colors === [null]) {
                                            return;
                                        }

                                        $variants = (array) $get('variants');
                                        $exists = fn ($size, $color) => collect($variants)->contains(
                                            fn ($v) => strcasecmp((string) ($v['size'] ?? ''), (string) $size) === 0
                                                && strcasecmp((string) ($v['color'] ?? ''), (string) $color) === 0
                                        );

                                        foreach ($sizes as $size) {
                                            foreach ($colors as $color) {
                                                if ($exists($size, $color)) {
                                                    continue;
                                                }

                                                $variants[(string) Str::uuid()] = [
                                                    'size' => $size,
                                                    'color' => $color,
                                                    'sku' => null,
                                                    'price_kobo' => null,
                                                    'stock_quantity' => (int) ($get('generate_stock') ?: 0),
                                                ];
                                            }
                                        }

                                        $set('variants', $variants);
                                        $set('generate_sizes', []);
                                        $set('generate_colors', []);
                                    }),
                            ])->columnSpanFull(),
                        ])->columns(3),
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
                            ->itemLabel(fn (array $state) => collect([$state['size'] ?? null, $state['color'] ?? null])->filter()->implode(' / ') ?: 'New variant')
                            ->collapsible()
                            ->columnSpanFull()
                            // Deliberately NOT required: a variant product with no
                            // variants is still sellable — customers order it and
                            // the studio confirms the size/colour afterwards, which
                            // is why those orders get 5 extra days. See
                            // Order::needs_variant_confirmation.
                            ->helperText(fn (Forms\Get $get): ?string => $get('has_variants')
                                ? 'Leave this empty if the options aren\'t decided yet. Customers can still order, and you\'ll confirm their size/colour afterwards — those orders take '.Order::EXTRA_DAYS_WITHOUT_VARIANT.' days longer.'
                                : null),
                    ]),

                Forms\Components\Section::make('Featured image')
                    ->description('The primary photo used on product cards, the shop grid, and social previews.')
                    ->schema([
                        VerifiedUpload::apply(
                            Forms\Components\FileUpload::make('featured_image_path')
                                ->label('')
                                ->image()
                                ->maxSize(5120)
                                ->directory('products')
                                ->disk(config('filesystems.image_disk'))
                                ->fetchFileInformation(false)
                        ),
                    ]),

                Forms\Components\Section::make('Gallery images')
                    ->description('Additional photos shown in the product page gallery.')
                    ->schema([
                        Forms\Components\Repeater::make('images')
                            ->relationship()
                            ->label('')
                            ->schema([
                                VerifiedUpload::apply(
                                    Forms\Components\FileUpload::make('path')
                                        ->image()
                                        ->maxSize(5120)
                                        ->directory('products')
                                        ->disk(config('filesystems.image_disk'))
                                        ->fetchFileInformation(false)
                                        ->required()
                                ),
                                Forms\Components\TextInput::make('alt_text')->maxLength(255),
                                Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Add gallery image')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('SEO')
                    ->description('Auto-filled from the name and short description above — edit either field here to override.')
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')->maxLength(255),
                        Forms\Components\Textarea::make('meta_description')
                            ->rows(2)
                            ->maxLength(500)
                            ->helperText('Aim for around 155-160 characters — search engines truncate longer descriptions.'),
                    ])->columns(2)->collapsed(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // The thumbnail and the stock badge both need relations; without
            // this the list fires a query per row.
            ->modifyQueryUsing(fn ($query) => $query->with(['images', 'variants']))
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail')
                    ->label('')
                    ->state(fn (Product $record) => $record->featured_image_path ?: $record->images->first()?->path)
                    ->disk(config('filesystems.image_disk'))
                    ->size(44)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover']),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Product $record): ?string => $record->category?->name),

                Tables\Columns\TextColumn::make('price_kobo')
                    ->label('Price')
                    ->formatStateUsing(fn ($state) => '₦'.number_format($state / 100, 2))
                    ->sortable(),

                // Reads "24 in stock" / "180 across 12 options" / "Out of stock"
                // rather than a bare number that means nothing for variant
                // products (their own stock column stays 0).
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stock')
                    ->badge()
                    ->state(fn (Product $record): string => $record->stockSummary())
                    ->color(fn (Product $record): string => $record->canPurchase() ? 'success' : 'danger'),

                // Live/Draft, plus two clearly-labelled flags. These used to be
                // three unlabelled toggle switches stacked on top of each other,
                // which told you neither what they were nor what they were set to.
                Tables\Columns\TextColumn::make('is_published')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Live' : 'Draft')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->state(fn (Product $record): bool => (bool) $record->is_featured)
                    ->icon(fn (bool $state): string => $state ? 'heroicon-s-star' : 'heroicon-o-minus')
                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray'),

                Tables\Columns\IconColumn::make('is_preorder')
                    ->label('Pre-order')
                    ->state(fn (Product $record): bool => (bool) $record->is_preorder)
                    ->icon(fn (bool $state): string => $state ? 'heroicon-o-clock' : 'heroicon-o-minus')
                    ->color(fn (bool $state): string => $state ? 'info' : 'gray'),
            ])
            ->defaultSort('created_at', 'desc')
            // Without this Filament's "Sort by" control reads "-", because the
            // default sort column isn't one of the visible ones.
            ->defaultSortOptionLabel('Newest first')
            // Infinite scrolling loads the next chunk automatically; these are
            // the starting chunk and the ceiling the pager would offer.
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn () => Category::orderBy('name')->pluck('name', 'id')),
                Tables\Filters\TernaryFilter::make('is_published')->label('Live'),
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
