<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscountCodeResource\Pages;
use App\Models\DiscountCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;

class DiscountCodeResource extends Resource
{
    protected static ?string $model = DiscountCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Discount code')->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->helperText('Customers enter this at checkout, not case-sensitive.'),
                Forms\Components\Select::make('type')
                    ->options([
                        DiscountCode::TYPE_PERCENTAGE => 'Percentage off',
                        DiscountCode::TYPE_FIXED => 'Fixed amount off',
                    ])
                    ->default(DiscountCode::TYPE_PERCENTAGE)
                    ->required()
                    ->live(),
                Forms\Components\TextInput::make('value')
                    ->label(fn (Forms\Get $get) => $get('type') === DiscountCode::TYPE_FIXED ? 'Amount off (₦)' : 'Percent off')
                    ->numeric()
                    ->required()
                    ->suffix(fn (Forms\Get $get) => $get('type') === DiscountCode::TYPE_PERCENTAGE ? '%' : null)
                    ->prefix(fn (Forms\Get $get) => $get('type') === DiscountCode::TYPE_FIXED ? '₦' : null)
                    ->maxValue(fn (Forms\Get $get) => $get('type') === DiscountCode::TYPE_PERCENTAGE ? 100 : null)
                    ->formatStateUsing(fn ($state, Forms\Get $get) => $state !== null && $get('type') === DiscountCode::TYPE_FIXED ? $state / 100 : $state)
                    ->dehydrateStateUsing(fn ($state, Forms\Get $get) => $get('type') === DiscountCode::TYPE_FIXED ? (int) round($state * 100) : (int) $state),
                Forms\Components\Toggle::make('is_active')->default(true),
            ])->columns(2),

            Forms\Components\Section::make('Limits')->schema([
                Forms\Components\TextInput::make('max_uses')
                    ->label('Max uses (optional)')
                    ->numeric()
                    ->helperText('Leave blank for unlimited uses.'),
                Forms\Components\TextInput::make('min_order_kobo')
                    ->label('Minimum order (₦, optional)')
                    ->numeric()
                    ->prefix('₦')
                    ->formatStateUsing(fn ($state) => $state !== null ? $state / 100 : null)
                    ->dehydrateStateUsing(fn ($state) => $state !== null && $state !== '' ? (int) round($state * 100) : null),
                Forms\Components\DateTimePicker::make('starts_at')->label('Starts (optional)'),
                Forms\Components\DateTimePicker::make('expires_at')->label('Expires (optional)'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('code')->weight(FontWeight::Bold)->searchable(),
                        Tables\Columns\TextColumn::make('type')
                            ->badge()
                            ->formatStateUsing(fn (DiscountCode $record) => $record->label()),
                    ]),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('used_count')
                            ->label('Used')
                            ->formatStateUsing(fn (DiscountCode $record) => $record->max_uses ? "{$record->used_count} / {$record->max_uses}" : (string) $record->used_count),
                        Tables\Columns\IconColumn::make('is_active')->boolean(),
                    ]),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('starts_at')->dateTime('d M Y')->placeholder('—')->color('gray')->size('sm'),
                        Tables\Columns\TextColumn::make('expires_at')->dateTime('d M Y')->placeholder('—')->color('gray')->size('sm'),
                    ])->alignEnd(),
                ])->from('md'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
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
            'index' => Pages\ListDiscountCodes::route('/'),
            'create' => Pages\CreateDiscountCode::route('/create'),
            'edit' => Pages\EditDiscountCode::route('/{record}/edit'),
        ];
    }
}
