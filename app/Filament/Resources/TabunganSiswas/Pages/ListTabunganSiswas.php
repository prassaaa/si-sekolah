<?php

namespace App\Filament\Resources\TabunganSiswas\Pages;

use App\Filament\Resources\TabunganSiswas\TabunganSiswaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTabunganSiswas extends ListRecords
{
    protected static string $resource = TabunganSiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
