<?php

namespace App\Filament\Resources\IzinKeluars\Pages;

use App\Filament\Resources\IzinKeluars\IzinKeluarResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIzinKeluar extends CreateRecord
{
    protected static string $resource = IzinKeluarResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
