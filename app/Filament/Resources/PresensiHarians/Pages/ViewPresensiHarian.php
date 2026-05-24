<?php

namespace App\Filament\Resources\PresensiHarians\Pages;

use App\Filament\Resources\PresensiHarians\PresensiHarianResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPresensiHarian extends ViewRecord
{
    protected static string $resource = PresensiHarianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
