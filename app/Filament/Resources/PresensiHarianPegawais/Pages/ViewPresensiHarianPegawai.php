<?php

namespace App\Filament\Resources\PresensiHarianPegawais\Pages;

use App\Filament\Resources\PresensiHarianPegawais\PresensiHarianPegawaiResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPresensiHarianPegawai extends ViewRecord
{
    protected static string $resource = PresensiHarianPegawaiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
