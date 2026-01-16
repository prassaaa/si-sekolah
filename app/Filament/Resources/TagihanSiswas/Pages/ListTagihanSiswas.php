<?php

namespace App\Filament\Resources\TagihanSiswas\Pages;

use App\Filament\Resources\TagihanSiswas\TagihanSiswaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTagihanSiswas extends ListRecords
{
    protected static string $resource = TagihanSiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
