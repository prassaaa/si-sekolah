<?php

namespace App\Filament\Resources\KasMasuks\Pages;

use App\Filament\Resources\KasMasuks\KasMasukResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditKasMasuk extends EditRecord
{
    protected static string $resource = KasMasukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
