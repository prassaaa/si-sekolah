<?php

namespace App\Filament\Resources\SarprasPenghapusans\Pages;

use App\Filament\Resources\SarprasPenghapusans\SarprasPenghapusanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSarprasPenghapusans extends ListRecords
{
    protected static string $resource = SarprasPenghapusanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
