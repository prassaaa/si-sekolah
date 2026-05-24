<?php

namespace App\Filament\Resources\RfidScanLogs\Schemas;

use App\Models\Pegawai;
use App\Models\Siswa;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RfidScanLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail Scan')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('scanned_at')->label('Waktu Scan')->dateTime('d M Y H:i:s'),
                    TextEntry::make('uid')->label('UID Kartu')->copyable(),
                    TextEntry::make('jenis')
                        ->label('Jenis')
                        ->badge()
                        ->color(fn ($record) => $record->jenis_info['color'])
                        ->formatStateUsing(fn ($record) => $record->jenis_info['label']),
                    TextEntry::make('device.nama')->label('Device')->placeholder('-'),
                ]),
                TextEntry::make('pesan')->label('Pesan')->columnSpanFull(),
            ]),

            Section::make('Pemilik & Kartu')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('owner.nama')->label('Pemilik')->placeholder('Tidak teridentifikasi'),
                    TextEntry::make('owner_type')
                        ->label('Tipe')
                        ->formatStateUsing(fn (?string $state) => match ($state) {
                            Siswa::class => 'Siswa',
                            Pegawai::class => 'Pegawai',
                            default => '-',
                        }),
                ]),
                TextEntry::make('kartuRfid.status')->label('Status Kartu Saat Tap')->placeholder('-'),
            ]),

            Section::make('Payload (Audit)')->schema([
                TextEntry::make('request_payload')
                    ->label('Request')
                    ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '-')
                    ->columnSpanFull()
                    ->copyable(),
                TextEntry::make('response_payload')
                    ->label('Response')
                    ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '-')
                    ->columnSpanFull()
                    ->copyable(),
            ])->collapsible(),
        ]);
    }
}
