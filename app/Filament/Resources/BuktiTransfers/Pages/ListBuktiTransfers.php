<?php

namespace App\Filament\Resources\BuktiTransfers\Pages;

use App\Filament\Resources\BuktiTransfers\BuktiTransferResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBuktiTransfers extends ListRecords
{
    protected static string $resource = BuktiTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
