<?php

namespace App\Filament\Resources\SarprasPengadaans\Pages;

use App\Filament\Resources\SarprasPengadaans\SarprasPengadaanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSarprasPengadaan extends EditRecord
{
    protected static string $resource = SarprasPengadaanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
