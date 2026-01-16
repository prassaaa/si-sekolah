<?php

namespace App\Filament\Resources\Konselings\Pages;

use App\Filament\Resources\Konselings\KonselingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKonseling extends CreateRecord
{
    protected static string $resource = KonselingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
