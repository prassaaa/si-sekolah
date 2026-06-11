<?php

namespace App\Filament\Resources\Aduans\Pages;

use App\Filament\Resources\Aduans\AduanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAduan extends EditRecord
{
    protected static string $resource = AduanResource::class;

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
