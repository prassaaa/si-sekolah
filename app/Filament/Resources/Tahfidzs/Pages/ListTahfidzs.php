<?php

namespace App\Filament\Resources\Tahfidzs\Pages;

use App\Filament\Resources\Tahfidzs\TahfidzResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTahfidzs extends ListRecords
{
    protected static string $resource = TahfidzResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
