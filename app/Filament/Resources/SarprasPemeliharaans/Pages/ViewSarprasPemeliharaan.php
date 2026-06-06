<?php

namespace App\Filament\Resources\SarprasPemeliharaans\Pages;

use App\Filament\Resources\SarprasPemeliharaans\SarprasPemeliharaanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSarprasPemeliharaan extends ViewRecord
{
    protected static string $resource = SarprasPemeliharaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
