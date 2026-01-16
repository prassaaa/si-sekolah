<?php

namespace App\Filament\Resources\KategoriPembayarans\Pages;

use App\Filament\Resources\KategoriPembayarans\KategoriPembayaranResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKategoriPembayaran extends CreateRecord
{
    protected static string $resource = KategoriPembayaranResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
