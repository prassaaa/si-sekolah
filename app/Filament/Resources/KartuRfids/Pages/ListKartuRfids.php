<?php

namespace App\Filament\Resources\KartuRfids\Pages;

use App\Filament\Resources\KartuRfids\KartuRfidResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKartuRfids extends ListRecords
{
    protected static string $resource = KartuRfidResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
