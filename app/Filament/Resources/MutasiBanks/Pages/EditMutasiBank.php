<?php

namespace App\Filament\Resources\MutasiBanks\Pages;

use App\Filament\Resources\MutasiBanks\MutasiBankResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditMutasiBank extends EditRecord
{
    protected static string $resource = MutasiBankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
