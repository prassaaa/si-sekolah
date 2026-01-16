<?php

namespace App\Filament\Resources\KenaikanKelass\Pages;

use App\Filament\Resources\KenaikanKelass\KenaikanKelasResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewKenaikanKelas extends ViewRecord
{
    protected static string $resource = KenaikanKelasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
