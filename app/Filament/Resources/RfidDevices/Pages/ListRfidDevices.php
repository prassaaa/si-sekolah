<?php

namespace App\Filament\Resources\RfidDevices\Pages;

use App\Filament\Resources\RfidDevices\RfidDeviceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRfidDevices extends ListRecords
{
    protected static string $resource = RfidDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
