<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Product;
use App\Models\Review;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 3;

    public static function getRecordRouteKeyName(): ?string
    {
        return 'uuid';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Review')->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Product')
                    ->options(fn () => Product::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('reviewer_name')
                    ->label('Reviewer name')
                    ->helperText('Used for testimonials imported from social media/WhatsApp — leave blank when linking a real customer account below.')
                    ->maxLength(255),
                Forms\Components\Select::make('rating')
                    ->options([1 => '★☆☆☆☆', 2 => '★★☆☆☆', 3 => '★★★☆☆', 4 => '★★★★☆', 5 => '★★★★★'])
                    ->required(),
                Forms\Components\TextInput::make('title')->maxLength(255),
                Forms\Components\Textarea::make('body')->rows(4)->columnSpanFull(),
                Forms\Components\FileUpload::make('screenshot_path')
                    ->label('Screenshot')
                    ->helperText('A screenshot of a testimonial from Instagram, WhatsApp, etc. — shown alongside the review.')
                    ->image()
                    ->maxSize(5120)
                    ->directory('reviews')
                    ->disk('public')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_approved')
                    ->label('Approved (visible on the storefront)')
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('screenshot_path')->disk('public')->label('Screenshot'),
                Tables\Columns\TextColumn::make('product.name')->label('Product')->searchable()->limit(30),
                Tables\Columns\TextColumn::make('reviewer')
                    ->label('Reviewer')
                    ->state(fn (Review $record) => $record->displayName()),
                Tables\Columns\IconColumn::make('is_verified_purchase')->boolean()->label('Verified'),
                Tables\Columns\TextColumn::make('rating')->formatStateUsing(fn ($state) => str_repeat('★', $state).str_repeat('☆', 5 - $state)),
                Tables\Columns\TextColumn::make('title')->limit(30)->placeholder('—'),
                Tables\Columns\TextColumn::make('body')->limit(50)->wrap(),
                Tables\Columns\IconColumn::make('is_approved')->boolean()->label('Approved'),
                Tables\Columns\TextColumn::make('created_at')->date()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_approved'),
                Tables\Filters\TernaryFilter::make('is_verified_purchase'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Review $record) => ! $record->is_approved)
                    ->action(function (Review $record) {
                        $record->update(['is_approved' => true]);
                        Notification::make()->title('Review approved')->success()->send();
                    }),
                Tables\Actions\Action::make('unapprove')
                    ->label('Hide')
                    ->icon('heroicon-o-eye-slash')
                    ->color('gray')
                    ->visible(fn (Review $record) => $record->is_approved)
                    ->action(function (Review $record) {
                        $record->update(['is_approved' => false]);
                        Notification::make()->title('Review hidden')->success()->send();
                    }),
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
            'index' => Pages\ListReviews::route('/'),
            'create' => Pages\CreateReview::route('/create'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('is_approved', false)->count();
    }
}
