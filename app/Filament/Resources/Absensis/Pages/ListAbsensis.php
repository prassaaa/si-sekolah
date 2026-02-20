<?php

namespace App\Filament\Resources\Absensis\Pages;

use App\Filament\Resources\Absensis\AbsensiResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAbsensis extends ListRecords
{
    protected static string $resource = AbsensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('input_absensi')
                ->label('Input Absensi')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('primary')
                ->url(fn (): string => AbsensiResource::getUrl('input')),
            CreateAction::make()
                ->label('Tambah Manual'),
        ];
    }
}
