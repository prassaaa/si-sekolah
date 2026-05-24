<?php

namespace App\Filament\Resources\PresensiHarians\Pages;

use App\Filament\Resources\PresensiHarians\PresensiHarianResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPresensiHarian extends EditRecord
{
    protected static string $resource = PresensiHarianResource::class;

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
