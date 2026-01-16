<?php

namespace App\Filament\Resources\KenaikanKelass\Pages;

use App\Filament\Resources\KenaikanKelass\KenaikanKelasResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKenaikanKelas extends EditRecord
{
    protected static string $resource = KenaikanKelasResource::class;

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
