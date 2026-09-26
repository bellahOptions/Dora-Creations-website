<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    /**
     * How many more products each scroll reaches for. The list simply grows,
     * so the shopper never loses their place to a page reload.
     */
    public const CHUNK_SIZE = 25;

    /**
     * Mirrors Filament's own list-records view, with the infinite-scroll
     * sentinel appended after the table.
     */
    protected static string $view = 'filament.resources.products.list-products';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
