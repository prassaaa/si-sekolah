<?php

namespace App\Filament\Resources\Tahfidzs\Pages;

use App\Filament\Resources\Tahfidzs\TahfidzResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTahfidz extends EditRecord
{
    protected static string $resource = TahfidzResource::class;

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
