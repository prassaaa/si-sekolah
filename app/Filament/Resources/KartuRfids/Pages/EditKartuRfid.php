<?php

namespace App\Filament\Resources\KartuRfids\Pages;

use App\Filament\Resources\KartuRfids\KartuRfidResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditKartuRfid extends EditRecord
{
    protected static string $resource = KartuRfidResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
