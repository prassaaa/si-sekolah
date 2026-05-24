<?php

namespace App\Filament\Resources\RfidScanLogs\Pages;

use App\Filament\Resources\RfidScanLogs\RfidScanLogResource;
use Filament\Resources\Pages\ListRecords;

class ListRfidScanLogs extends ListRecords
{
    protected static string $resource = RfidScanLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
