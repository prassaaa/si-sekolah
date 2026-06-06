<?php

namespace App\Filament\Resources\SarprasPeminjamans\Pages;

use App\Filament\Resources\SarprasPeminjamans\SarprasPeminjamanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSarprasPeminjaman extends ViewRecord
{
    protected static string $resource = SarprasPeminjamanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
