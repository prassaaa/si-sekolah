<?php

namespace App\Filament\Resources\JamPelajarans\Pages;

use App\Filament\Resources\JamPelajarans\JamPelajaranResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewJamPelajaran extends ViewRecord
{
    protected static string $resource = JamPelajaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
