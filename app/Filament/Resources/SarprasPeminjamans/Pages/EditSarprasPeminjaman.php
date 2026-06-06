<?php

namespace App\Filament\Resources\SarprasPeminjamans\Pages;

use App\Filament\Resources\SarprasPeminjamans\SarprasPeminjamanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditSarprasPeminjaman extends EditRecord
{
    protected static string $resource = SarprasPeminjamanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
