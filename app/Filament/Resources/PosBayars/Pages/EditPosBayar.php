<?php

namespace App\Filament\Resources\PosBayars\Pages;

use App\Filament\Resources\PosBayars\PosBayarResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditPosBayar extends EditRecord
{
    protected static string $resource = PosBayarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
