<?php

namespace App\Filament\Resources\Sekolahs\Pages;

use App\Filament\Resources\Sekolahs\SekolahResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSekolahs extends ListRecords
{
    protected static string $resource = SekolahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
