<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdModalResource\Pages;
use App\Models\AdModal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AdModalResource extends Resource
{
    protected static ?string $model = AdModal::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Site';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Ad Modals';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Content')->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\RichEditor::make('body')
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('image_path')
                    ->label('Image (optional)')
                    ->image()
                    ->maxSize(5120)
                    ->directory('ad-modals')
                    ->disk(config('filesystems.image_disk'))
                    ->fetchFileInformation(false)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('cta_label')
                    ->label('Button label (optional)')
                    ->maxLength(255),
                Forms\Components\TextInput::make('cta_url')
                    ->label('Button link (optional)')
                    ->helperText('A full URL or a path like /shop.')
                    ->maxLength(255),
            ])->columns(2),

            Forms\Components\Section::make('Display rules')->schema([
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
                Forms\Components\Select::make('frequency')
                    ->label('Show')
                    ->options([
                        AdModal::FREQUENCY_SESSION => 'Once per browser session',
                        AdModal::FREQUENCY_EVERY_VISIT => 'Every page load',
                    ])
                    ->default(AdModal::FREQUENCY_SESSION)
                    ->required(),
                Forms\Components\TextInput::make('delay_seconds')
                    ->label('Delay before showing (seconds)')
                    ->numeric()
                    ->default(2)
                    ->required(),
                Forms\Components\DateTimePicker::make('starts_at')
                    ->label('Starts (optional)')
                    ->helperText('Leave blank to start immediately.'),
                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Expires (optional)')
                    ->helperText('Leave blank to run indefinitely.'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('title')->weight('bold')->searchable(),
                        Tables\Columns\TextColumn::make('frequency')
                            ->formatStateUsing(fn (string $state) => $state === AdModal::FREQUENCY_SESSION ? 'Once per session' : 'Every page load')
                            ->color('gray')->size('sm'),
                    ]),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('starts_at')->label('Starts')->dateTime('d M Y, H:i')->placeholder('Immediately')->color('gray')->size('sm'),
                        Tables\Columns\TextColumn::make('expires_at')->label('Expires')->dateTime('d M Y, H:i')->placeholder('Never')->color('gray')->size('sm'),
                    ]),
                    Tables\Columns\IconColumn::make('is_active')->boolean(),
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
            'index' => Pages\ListAdModals::route('/'),
            'create' => Pages\CreateAdModal::route('/create'),
            'edit' => Pages\EditAdModal::route('/{record}/edit'),
        ];
    }
}
