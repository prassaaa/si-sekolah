<?php

namespace App\Filament\Resources\SarprasPenghapusans\Pages;

use App\Filament\Resources\SarprasPenghapusans\SarprasPenghapusanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSarprasPenghapusan extends ViewRecord
{
    protected static string $resource = SarprasPenghapusanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
