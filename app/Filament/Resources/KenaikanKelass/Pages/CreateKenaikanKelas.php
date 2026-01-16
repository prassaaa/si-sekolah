<?php

namespace App\Filament\Resources\KenaikanKelass\Pages;

use App\Filament\Resources\KenaikanKelass\KenaikanKelasResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKenaikanKelas extends CreateRecord
{
    protected static string $resource = KenaikanKelasResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
