<?php

namespace App\Filament\Resources\JabatanPegawais\Pages;

use App\Filament\Resources\JabatanPegawais\JabatanPegawaiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJabatanPegawais extends ListRecords
{
    protected static string $resource = JabatanPegawaiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
