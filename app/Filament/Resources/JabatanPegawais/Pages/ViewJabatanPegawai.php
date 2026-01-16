<?php

namespace App\Filament\Resources\JabatanPegawais\Pages;

use App\Filament\Resources\JabatanPegawais\JabatanPegawaiResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewJabatanPegawai extends ViewRecord
{
    protected static string $resource = JabatanPegawaiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
