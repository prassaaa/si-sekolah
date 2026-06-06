<?php

namespace App\Filament\Resources\SarprasPengadaans\Pages;

use App\Filament\Resources\SarprasPengadaans\SarprasPengadaanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSarprasPengadaans extends ListRecords
{
    protected static string $resource = SarprasPengadaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
