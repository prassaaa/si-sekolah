<?php

namespace App\Filament\Resources\RfidScanLogs\Pages;

use App\Filament\Resources\RfidScanLogs\RfidScanLogResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRfidScanLog extends ViewRecord
{
    protected static string $resource = RfidScanLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
