<?php

namespace App\Filament\Resources\Aduans\Pages;

use App\Filament\Resources\Aduans\AduanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAduan extends CreateRecord
{
    protected static string $resource = AduanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
