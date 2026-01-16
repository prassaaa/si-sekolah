<?php

namespace App\Filament\Resources\Pelanggarans\Pages;

use App\Filament\Resources\Pelanggarans\PelanggaranResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPelanggarans extends ListRecords
{
    protected static string $resource = PelanggaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
