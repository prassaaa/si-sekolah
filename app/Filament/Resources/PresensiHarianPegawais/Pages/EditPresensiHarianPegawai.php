<?php

namespace App\Filament\Resources\PresensiHarianPegawais\Pages;

use App\Filament\Resources\PresensiHarianPegawais\PresensiHarianPegawaiResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPresensiHarianPegawai extends EditRecord
{
    protected static string $resource = PresensiHarianPegawaiResource::class;

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
