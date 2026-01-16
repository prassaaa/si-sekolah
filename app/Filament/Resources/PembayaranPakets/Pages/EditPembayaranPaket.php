<?php

namespace App\Filament\Resources\PembayaranPakets\Pages;

use App\Filament\Resources\PembayaranPakets\PembayaranPaketResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditPembayaranPaket extends EditRecord
{
    protected static string $resource = PembayaranPaketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
