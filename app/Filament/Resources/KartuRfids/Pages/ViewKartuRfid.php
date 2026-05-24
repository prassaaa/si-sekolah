<?php

namespace App\Filament\Resources\KartuRfids\Pages;

use App\Filament\Resources\KartuRfids\KartuRfidResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewKartuRfid extends ViewRecord
{
    protected static string $resource = KartuRfidResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
