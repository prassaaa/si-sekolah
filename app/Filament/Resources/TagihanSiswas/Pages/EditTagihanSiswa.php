<?php

namespace App\Filament\Resources\TagihanSiswas\Pages;

use App\Filament\Resources\TagihanSiswas\TagihanSiswaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTagihanSiswa extends EditRecord
{
    protected static string $resource = TagihanSiswaResource::class;

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
