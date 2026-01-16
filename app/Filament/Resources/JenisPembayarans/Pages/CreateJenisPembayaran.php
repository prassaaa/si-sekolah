<?php

namespace App\Filament\Resources\JenisPembayarans\Pages;

use App\Filament\Resources\JenisPembayarans\JenisPembayaranResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJenisPembayaran extends CreateRecord
{
    protected static string $resource = JenisPembayaranResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
