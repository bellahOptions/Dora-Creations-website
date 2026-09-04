<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Customer';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('avatar_path')
                ->label('Avatar')
                ->avatar()
                ->directory('avatars')
                ->disk(config('filesystems.image_disk'))
                ->fetchFileInformation(false)
                ->columnSpanFull(),
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\TextInput::make('email')->disabled()->dehydrated(false),
            Forms\Components\TextInput::make('phone')->tel()->maxLength(20),
            Forms\Components\Toggle::make('is_admin')->label('Admin access'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\ImageColumn::make('avatar_path')->disk(config('filesystems.image_disk'))->circular()->label('Avatar')->grow(false),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('name')->searchable()->sortable()->weight('bold'),
                        Tables\Columns\TextColumn::make('email')->searchable()->color('gray')->size('sm'),
                        Tables\Columns\TextColumn::make('phone')->toggleable()->color('gray')->size('sm'),
                    ]),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('status')
                            ->badge()
                            ->color(fn (string $state) => $state === User::STATUS_SUSPENDED ? 'danger' : 'success'),
                        Tables\Columns\IconColumn::make('is_admin')->boolean()->label('Admin'),
                    ]),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('orders_count')->counts('orders')->label('Orders'),
                        Tables\Columns\TextColumn::make('created_at')->label('Joined')->date()->sortable()->color('gray')->size('sm'),
                    ])->alignEnd(),
                ])->from('md'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    User::STATUS_ACTIVE => 'Active',
                    User::STATUS_SUSPENDED => 'Suspended',
                ]),
                Tables\Filters\TernaryFilter::make('is_admin'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => ! $record->isSuspended())
                    ->action(function (User $record) {
                        $record->suspend();
                        Notification::make()->title('Customer suspended')->success()->send();
                    }),
                Tables\Actions\Action::make('reactivate')
                    ->label('Reactivate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => $record->isSuspended())
                    ->action(function (User $record) {
                        $record->reactivate();
                        Notification::make()->title('Customer reactivated')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
