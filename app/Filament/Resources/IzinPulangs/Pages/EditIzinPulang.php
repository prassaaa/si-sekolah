<?php

namespace App\Filament\Resources\IzinPulangs\Pages;

use App\Filament\Resources\IzinPulangs\IzinPulangResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIzinPulang extends EditRecord
{
    protected static string $resource = IzinPulangResource::class;

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
