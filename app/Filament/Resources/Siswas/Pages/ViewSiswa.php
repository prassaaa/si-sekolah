<?php

namespace App\Filament\Resources\Siswas\Pages;

use App\Filament\Resources\Siswas\SiswaResource;
use App\Services\Kesiswaan\BukuPribadiService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewSiswa extends ViewRecord
{
    protected static string $resource = SiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pratinjauBukuPribadi')
                ->label('Pratinjau Buku Pribadi')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn (): string => route('siswa.buku-pribadi', $this->getRecord()), shouldOpenInNewTab: true),
            Action::make('cetakBukuPribadi')
                ->label('Cetak Buku Pribadi')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(function (): StreamedResponse {
                    $service = app(BukuPribadiService::class);
                    $record = $this->getRecord();

                    return response()->streamDownload(
                        fn () => print ($service->pdf($record)->output()),
                        $service->filename($record),
                    );
                }),
            EditAction::make(),
            DeleteAction::make(),
            RestoreAction::make(),
            ForceDeleteAction::make(),
        ];
    }
}
