<?php

namespace App\Filament\Resources\SarprasPenghapusans\Pages;

use App\Filament\Resources\SarprasPenghapusans\SarprasPenghapusanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditSarprasPenghapusan extends EditRecord
{
    protected static string $resource = SarprasPenghapusanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
