<?php

namespace App\Filament\Resources\AdModalResource\Pages;

use App\Filament\Resources\AdModalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdModals extends ListRecords
{
    protected static string $resource = AdModalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
