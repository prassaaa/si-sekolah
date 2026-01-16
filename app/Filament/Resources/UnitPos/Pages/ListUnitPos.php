<?php

namespace App\Filament\Resources\UnitPos\Pages;

use App\Filament\Resources\UnitPos\UnitPosResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUnitPos extends ListRecords
{
    protected static string $resource = UnitPosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
