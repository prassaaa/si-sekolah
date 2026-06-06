<?php

namespace App\Filament\Resources\SarprasBarangs\Pages;

use App\Filament\Resources\SarprasBarangs\SarprasBarangResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSarprasBarangs extends ListRecords
{
    protected static string $resource = SarprasBarangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
