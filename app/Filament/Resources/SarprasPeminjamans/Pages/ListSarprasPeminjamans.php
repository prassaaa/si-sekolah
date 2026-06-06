<?php

namespace App\Filament\Resources\SarprasPeminjamans\Pages;

use App\Filament\Resources\SarprasPeminjamans\SarprasPeminjamanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSarprasPeminjamans extends ListRecords
{
    protected static string $resource = SarprasPeminjamanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
