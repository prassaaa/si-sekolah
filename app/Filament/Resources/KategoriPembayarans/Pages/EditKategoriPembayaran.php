<?php

namespace App\Filament\Resources\KategoriPembayarans\Pages;

use App\Filament\Resources\KategoriPembayarans\KategoriPembayaranResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKategoriPembayaran extends EditRecord
{
    protected static string $resource = KategoriPembayaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
