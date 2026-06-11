<?php

namespace App\Filament\Resources\Aduans\Pages;

use App\Filament\Resources\Aduans\AduanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAduans extends ListRecords
{
    protected static string $resource = AduanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
