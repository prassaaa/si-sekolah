<?php

namespace App\Filament\Resources\PresensiHarians\Pages;

use App\Filament\Resources\PresensiHarians\PresensiHarianResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPresensiHarians extends ListRecords
{
    protected static string $resource = PresensiHarianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
