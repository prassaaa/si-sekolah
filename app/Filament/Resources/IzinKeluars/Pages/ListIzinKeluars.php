<?php

namespace App\Filament\Resources\IzinKeluars\Pages;

use App\Filament\Resources\IzinKeluars\IzinKeluarResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIzinKeluars extends ListRecords
{
    protected static string $resource = IzinKeluarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
