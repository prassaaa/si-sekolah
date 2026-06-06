<?php

namespace App\Filament\Resources\SarprasPengadaans\Pages;

use App\Filament\Resources\SarprasPengadaans\SarprasPengadaanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSarprasPengadaan extends ViewRecord
{
    protected static string $resource = SarprasPengadaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
