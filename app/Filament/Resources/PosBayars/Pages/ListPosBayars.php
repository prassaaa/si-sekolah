<?php

namespace App\Filament\Resources\PosBayars\Pages;

use App\Filament\Resources\PosBayars\PosBayarResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPosBayars extends ListRecords
{
    protected static string $resource = PosBayarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
