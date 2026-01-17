<?php

namespace App\Filament\Resources\SettingGajis\Pages;

use App\Filament\Resources\SettingGajis\SettingGajiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSettingGajis extends ListRecords
{
    protected static string $resource = SettingGajiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
