<?php

namespace App\Filament\Resources\PembayaranPakets\Pages;

use App\Filament\Resources\PembayaranPakets\PembayaranPaketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPembayaranPakets extends ListRecords
{
    protected static string $resource = PembayaranPaketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
