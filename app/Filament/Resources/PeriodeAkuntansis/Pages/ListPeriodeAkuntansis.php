<?php

namespace App\Filament\Resources\PeriodeAkuntansis\Pages;

use App\Filament\Resources\PeriodeAkuntansis\PeriodeAkuntansiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPeriodeAkuntansis extends ListRecords
{
    protected static string $resource = PeriodeAkuntansiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Periode'),
        ];
    }
}
