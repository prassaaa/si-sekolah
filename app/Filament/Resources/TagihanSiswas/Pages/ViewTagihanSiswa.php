<?php

namespace App\Filament\Resources\TagihanSiswas\Pages;

use App\Filament\Resources\TagihanSiswas\TagihanSiswaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTagihanSiswa extends ViewRecord
{
    protected static string $resource = TagihanSiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
