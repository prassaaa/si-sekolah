<?php

namespace App\Filament\Resources\SarprasBarangs\Pages;

use App\Filament\Resources\SarprasBarangs\SarprasBarangResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSarprasBarang extends ViewRecord
{
    protected static string $resource = SarprasBarangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
            RestoreAction::make(),
            ForceDeleteAction::make(),
        ];
    }
}
