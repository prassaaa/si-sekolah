<?php

namespace App\Filament\Resources\KenaikanKelass\Pages;

use App\Filament\Resources\KenaikanKelass\KenaikanKelasResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKenaikanKelass extends ListRecords
{
    protected static string $resource = KenaikanKelasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
