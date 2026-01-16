<?php

namespace App\Filament\Resources\JabatanPegawais\Pages;

use App\Filament\Resources\JabatanPegawais\JabatanPegawaiResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditJabatanPegawai extends EditRecord
{
    protected static string $resource = JabatanPegawaiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
