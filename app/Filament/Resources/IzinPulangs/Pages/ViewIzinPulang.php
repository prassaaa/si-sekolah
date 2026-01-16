<?php

namespace App\Filament\Resources\IzinPulangs\Pages;

use App\Filament\Resources\IzinPulangs\IzinPulangResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewIzinPulang extends ViewRecord
{
    protected static string $resource = IzinPulangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
