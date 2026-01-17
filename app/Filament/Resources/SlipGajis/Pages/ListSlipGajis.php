<?php

namespace App\Filament\Resources\SlipGajis\Pages;

use App\Filament\Resources\SlipGajis\SlipGajiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSlipGajis extends ListRecords
{
    protected static string $resource = SlipGajiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
