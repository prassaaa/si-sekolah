<?php

namespace App\Filament\Resources\KategoriPembayarans\Pages;

use App\Filament\Resources\KategoriPembayarans\KategoriPembayaranResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewKategoriPembayaran extends ViewRecord
{
    protected static string $resource = KategoriPembayaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
