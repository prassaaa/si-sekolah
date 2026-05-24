<?php

namespace App\Filament\Resources\RfidDevices\Pages;

use App\Filament\Resources\RfidDevices\RfidDeviceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRfidDevice extends EditRecord
{
    protected static string $resource = RfidDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
