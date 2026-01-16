<?php

namespace App\Filament\Resources\IzinPulangs\Schemas;

use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class IzinPulangInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Izin')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('siswa.nama')
                                ->label('Nama Siswa'),

                            TextEntry::make('siswa.nis')
                                ->label('NIS'),

                            TextEntry::make('siswa.kelas.nama')
                                ->label('Kelas'),
                        ]),

                        Grid::make(3)->schema([
                            TextEntry::make('tanggal')
                                ->label('Tanggal')
                                ->date('d M Y'),

                            TextEntry::make('jam_pulang')
                                ->label('Jam Pulang')
                                ->time('H:i'),

                            TextEntry::make('kategori')
                                ->label('Kategori')
                                ->badge()
                                ->formatStateUsing(fn (string $state) => match ($state) {
                                    'sakit' => 'Sakit',
                                    'kepentingan_keluarga' => 'Kepentingan Keluarga',
                                    'urusan_pribadi' => 'Urusan Pribadi',
                                    'lainnya' => 'Lainnya',
                                    default => $state,
                                })
                                ->color(fn (string $state) => match ($state) {
                                    'sakit' => 'danger',
                                    'kepentingan_keluarga' => 'info',
                                    'urusan_pribadi' => 'warning',
                                    default => 'gray',
                                }),
                        ]),

                        TextEntry::make('alasan')
                            ->label('Alasan'),
                    ]),

                Section::make('Data Penjemput')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('penjemput_nama')
                                ->label('Nama Penjemput')
                                ->placeholder('-'),

                            TextEntry::make('penjemput_hubungan')
                                ->label('Hubungan')
                                ->placeholder('-'),

                            TextEntry::make('penjemput_telepon')
                                ->label('Telepon')
                                ->placeholder('-'),
                        ]),
                    ])
                    ->collapsible(),

                Section::make('Status & Catatan')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('petugas.nama')
                                ->label('Petugas')
                                ->placeholder('-'),

                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn (string $state) => match ($state) {
                                    'diizinkan' => 'Diizinkan',
                                    'ditolak' => 'Ditolak',
                                    'pending' => 'Pending',
                                    default => $state,
                                })
                                ->color(fn (string $state) => match ($state) {
                                    'diizinkan' => 'success',
                                    'ditolak' => 'danger',
                                    'pending' => 'warning',
                                    default => 'gray',
                                }),
                        ]),

                        TextEntry::make('catatan')
                            ->label('Catatan')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Informasi Sistem')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('created_at')
                                ->label('Dibuat')
                                ->dateTime('d M Y H:i'),

                            TextEntry::make('updated_at')
                                ->label('Diperbarui')
                                ->dateTime('d M Y H:i'),
                        ]),
                    ])
                    ->collapsed(),
            ]);
    }
}
