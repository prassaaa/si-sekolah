<?php

namespace App\Filament\Resources\TabunganSiswas\Pages;

use App\Filament\Resources\TabunganSiswas\TabunganSiswaResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditTabunganSiswa extends EditRecord
{
    protected static string $resource = TabunganSiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
