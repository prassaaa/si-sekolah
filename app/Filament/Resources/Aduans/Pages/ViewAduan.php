<?php

namespace App\Filament\Resources\Aduans\Pages;

use App\Filament\Resources\Aduans\AduanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAduan extends ViewRecord
{
    protected static string $resource = AduanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
