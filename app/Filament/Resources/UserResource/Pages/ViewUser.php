<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Infolists\Components as Infolist;
use Filament\Infolists\Infolist as InfolistLayout;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('suspend')
                ->label('Suspend')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => ! $this->record->isSuspended())
                ->action(function () {
                    $this->record->suspend();
                    Notification::make()->title('Customer suspended')->success()->send();
                }),
            Actions\Action::make('reactivate')
                ->label('Reactivate')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->isSuspended())
                ->action(function () {
                    $this->record->reactivate();
                    Notification::make()->title('Customer reactivated')->success()->send();
                }),
        ];
    }

    public function infolist(InfolistLayout $infolist): InfolistLayout
    {
        return $infolist->schema([
            Infolist\Section::make('Customer')
                ->schema([
                    Infolist\TextEntry::make('name'),
                    Infolist\TextEntry::make('email'),
                    Infolist\TextEntry::make('phone')->placeholder('—'),
                    Infolist\TextEntry::make('status')->badge()->color(fn (string $state) => $state === User::STATUS_SUSPENDED ? 'danger' : 'success'),
                    Infolist\TextEntry::make('created_at')->label('Joined')->dateTime('d M Y'),
                ])->columns(3),

            Infolist\Section::make('Addresses')
                ->schema([
                    Infolist\RepeatableEntry::make('addresses')
                        ->hiddenLabel()
                        ->schema([
                            Infolist\TextEntry::make('label')->placeholder('Address'),
                            Infolist\TextEntry::make('full_name'),
                            Infolist\TextEntry::make('phone'),
                            Infolist\TextEntry::make('toSingleLine')->label('Address')->getStateUsing(fn ($record) => $record->toSingleLine())->columnSpanFull(),
                        ])
                        ->columns(3),
                ])
                ->visible(fn () => $this->record->addresses()->exists()),

            Infolist\Section::make('Orders')
                ->schema([
                    Infolist\RepeatableEntry::make('orders')
                        ->hiddenLabel()
                        ->schema([
                            Infolist\TextEntry::make('order_number'),
                            Infolist\TextEntry::make('status')->formatStateUsing(fn ($record) => $record->statusLabel()),
                            Infolist\TextEntry::make('total_kobo')->label('Total')->formatStateUsing(fn ($state) => '₦'.number_format($state / 100, 2)),
                            Infolist\TextEntry::make('created_at')->dateTime('d M Y'),
                        ])
                        ->columns(4),
                ])
                ->visible(fn () => $this->record->orders()->exists()),
        ]);
    }
}
