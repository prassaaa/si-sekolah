<?php

namespace App\Filament\Resources\UnitPos\Pages;

use App\Filament\Resources\UnitPos\UnitPosResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditUnitPos extends EditRecord
{
    protected static string $resource = UnitPosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
