<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SlideResource\Pages;
use App\Models\Slide;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SlideResource extends Resource
{
    protected static ?string $model = Slide::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Site';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Homepage slides';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('headline')->required()->maxLength(255),
            Forms\Components\TextInput::make('subheadline')->maxLength(255),
            Forms\Components\FileUpload::make('image_path')
                ->image()
                ->maxSize(5120)
                ->directory('slides')
                ->disk(config('filesystems.image_disk'))
                ->required()
                ->columnSpanFull(),
            Forms\Components\TextInput::make('cta_label')->label('Button label')->maxLength(255),
            Forms\Components\TextInput::make('cta_url')->label('Button link')->maxLength(255),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\ImageColumn::make('image_path')->disk(config('filesystems.image_disk'))->grow(false),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('headline')->searchable()->weight('bold'),
                        Tables\Columns\TextColumn::make('sort_order')->sortable()->color('gray')->size('sm'),
                    ]),
                    Tables\Columns\IconColumn::make('is_active')->boolean(),
                ])->from('md'),
            ])
            ->defaultSort('sort_order')
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
            'index' => Pages\ListSlides::route('/'),
            'create' => Pages\CreateSlide::route('/create'),
            'edit' => Pages\EditSlide::route('/{record}/edit'),
        ];
    }
}
