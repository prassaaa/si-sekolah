<?php

namespace App\Filament\Resources\Pelanggarans\Pages;

use App\Filament\Resources\Pelanggarans\PelanggaranResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePelanggaran extends CreateRecord
{
    protected static string $resource = PelanggaranResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
