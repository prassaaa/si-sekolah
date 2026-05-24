<?php

namespace App\Filament\Resources\RfidDevices\Pages;

use App\Filament\Resources\RfidDevices\RfidDeviceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRfidDevice extends ViewRecord
{
    protected static string $resource = RfidDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
