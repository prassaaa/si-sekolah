<?php

namespace App\Filament\Resources\IzinPulangs\Pages;

use App\Filament\Resources\IzinPulangs\IzinPulangResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIzinPulang extends CreateRecord
{
    protected static string $resource = IzinPulangResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
