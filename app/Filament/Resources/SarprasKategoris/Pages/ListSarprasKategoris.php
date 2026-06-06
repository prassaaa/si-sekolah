<?php

namespace App\Filament\Resources\SarprasKategoris\Pages;

use App\Filament\Resources\SarprasKategoris\SarprasKategoriResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSarprasKategoris extends ListRecords
{
    protected static string $resource = SarprasKategoriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
