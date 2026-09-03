<?php

namespace App\Filament\Pages;

use App\Models\ActivityLog as ActivityLogModel;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ActivityLog extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Activity Log';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Activity Log';

    protected static string $view = 'filament.pages.activity-log';

    public function table(Table $table): Table
    {
        return $table
            ->query(ActivityLogModel::query()->with('causer'))
            ->heading('')
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Activity')
                    ->wrap()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === ActivityLogModel::TYPE_ADMIN ? 'Admin' : 'Visitor')
                    ->color(fn (string $state) => $state === ActivityLogModel::TYPE_ADMIN ? 'warning' : 'success'),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('By')
                    ->placeholder('Visitor'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->formatStateUsing(fn ($state) => $state->diffForHumans())
                    ->tooltip(fn (ActivityLogModel $record) => $record->created_at->format('d M Y, H:i'))
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options([
                    ActivityLogModel::TYPE_ADMIN => 'Admin activity',
                    ActivityLogModel::TYPE_VISITOR => 'Visitor activity',
                ]),
            ])
            ->poll('30s');
    }
}
