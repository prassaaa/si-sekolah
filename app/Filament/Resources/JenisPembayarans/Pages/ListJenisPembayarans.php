<?php

namespace App\Filament\Resources\JenisPembayarans\Pages;

use App\Filament\Resources\JenisPembayarans\JenisPembayaranResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJenisPembayarans extends ListRecords
{
    protected static string $resource = JenisPembayaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
