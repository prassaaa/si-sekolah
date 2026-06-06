<?php

namespace App\Filament\Resources\SarprasKategoris\Pages;

use App\Filament\Resources\SarprasKategoris\SarprasKategoriResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSarprasKategori extends CreateRecord
{
    protected static string $resource = SarprasKategoriResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
