<?php

namespace App\Filament\Resources\SarprasPemeliharaans\Pages;

use App\Filament\Resources\SarprasPemeliharaans\SarprasPemeliharaanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSarprasPemeliharaans extends ListRecords
{
    protected static string $resource = SarprasPemeliharaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
