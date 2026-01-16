<?php

namespace App\Filament\Resources\Konselings\Pages;

use App\Filament\Resources\Konselings\KonselingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKonselings extends ListRecords
{
    protected static string $resource = KonselingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
