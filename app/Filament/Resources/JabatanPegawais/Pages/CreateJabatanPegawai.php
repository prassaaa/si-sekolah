<?php

namespace App\Filament\Resources\JabatanPegawais\Pages;

use App\Filament\Resources\JabatanPegawais\JabatanPegawaiResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJabatanPegawai extends CreateRecord
{
    protected static string $resource = JabatanPegawaiResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
