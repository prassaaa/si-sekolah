<?php

namespace App\Filament\Resources\SaldoAwals\Pages;

use App\Filament\Resources\SaldoAwals\SaldoAwalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSaldoAwals extends ListRecords
{
    protected static string $resource = SaldoAwalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
