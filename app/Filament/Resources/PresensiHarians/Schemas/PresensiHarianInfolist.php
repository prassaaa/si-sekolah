<?php

namespace App\Filament\Resources\PresensiHarians\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PresensiHarianInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Siswa')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('siswa.nis')->label('NIS'),
                    TextEntry::make('siswa.nama')->label('Nama Siswa'),
                    TextEntry::make('siswa.kelas.nama')->label('Kelas'),
                    TextEntry::make('tanggal')->label('Tanggal')->date('d M Y'),
                ]),
            ]),

            Section::make('Kehadiran')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn ($record) => $record->status_info['color'])
                        ->formatStateUsing(fn ($record) => $record->status_info['label']),
                    TextEntry::make('jam_masuk')->label('Jam Masuk')->time('H:i'),
                    TextEntry::make('jam_pulang')->label('Jam Pulang')->time('H:i'),
                ]),
                Grid::make(2)->schema([
                    TextEntry::make('sumber_masuk')->label('Sumber Masuk')->badge(),
                    TextEntry::make('sumber_pulang')->label('Sumber Pulang')->badge(),
                ]),
                TextEntry::make('terlambat_menit')
                    ->label('Terlambat (menit)')
                    ->visible(fn ($record) => $record->terlambat_menit !== null),
                TextEntry::make('keterangan')->label('Keterangan')->columnSpanFull(),
            ]),

            Section::make('Audit')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('pencatat.name')->label('Dicatat Oleh')->placeholder('Sistem (RFID)'),
                    TextEntry::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
                ]),
            ])->collapsible(),
        ]);
    }
}
