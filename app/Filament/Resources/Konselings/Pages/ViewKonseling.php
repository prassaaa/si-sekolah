<?php

namespace App\Filament\Resources\Konselings\Pages;

use App\Filament\Resources\Konselings\KonselingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewKonseling extends ViewRecord
{
    protected static string $resource = KonselingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
