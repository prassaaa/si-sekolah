<?php

namespace App\Filament\Resources\Konselings\Pages;

use App\Filament\Resources\Konselings\KonselingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKonseling extends EditRecord
{
    protected static string $resource = KonselingResource::class;

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
