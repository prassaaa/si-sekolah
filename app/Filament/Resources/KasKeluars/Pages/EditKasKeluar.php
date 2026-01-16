<?php

namespace App\Filament\Resources\KasKeluars\Pages;

use App\Filament\Resources\KasKeluars\KasKeluarResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditKasKeluar extends EditRecord
{
    protected static string $resource = KasKeluarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
