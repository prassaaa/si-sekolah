<?php

namespace App\Filament\Resources\PresensiHarianPegawais\Pages;

use App\Filament\Resources\PresensiHarianPegawais\PresensiHarianPegawaiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPresensiHarianPegawais extends ListRecords
{
    protected static string $resource = PresensiHarianPegawaiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
