<?php

namespace App\Filament\Resources\IzinKeluars\Pages;

use App\Filament\Resources\IzinKeluars\IzinKeluarResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewIzinKeluar extends ViewRecord
{
    protected static string $resource = IzinKeluarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
