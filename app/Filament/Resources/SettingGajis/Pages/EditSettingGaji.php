<?php

namespace App\Filament\Resources\SettingGajis\Pages;

use App\Filament\Resources\SettingGajis\SettingGajiResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditSettingGaji extends EditRecord
{
    protected static string $resource = SettingGajiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
