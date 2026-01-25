<?php

namespace App\Filament\Resources\Pelanggarans\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PelanggaranInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Siswa')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('siswa.nama')
                                ->label('Nama Siswa'),
                            TextEntry::make('siswa.nisn')
                                ->label('NISN'),
                            TextEntry::make('semester.nama')
                                ->label('Semester')
                                ->formatStateUsing(fn ($state, $record) => $record->semester?->tahunAjaran?->nama.' - '.$state),
                        ]),
                    ]),

                Section::make('Data Pelanggaran')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('tanggal')
                                ->label('Tanggal Kejadian')
                                ->date('d M Y'),
                            TextEntry::make('pelapor.nama')
                                ->label('Pelapor')
                                ->placeholder('-'),
                        ]),

                        TextEntry::make('jenis_pelanggaran')
                            ->label('Jenis Pelanggaran'),

                        Grid::make(2)->schema([
                            TextEntry::make('kategori')
                                ->label('Kategori')
                                ->badge()
                                ->formatStateUsing(fn (string $state) => match ($state) {
                                    'ringan' => 'Ringan',
                                    'sedang' => 'Sedang',
                                    'berat' => 'Berat',
                                    default => $state,
                                })
                                ->color(fn (string $state) => match ($state) {
                                    'ringan' => 'info',
                                    'sedang' => 'warning',
                                    'berat' => 'danger',
                                    default => 'gray',
                                }),

                            TextEntry::make('poin')
                                ->label('Poin')
                                ->badge()
                                ->color('danger'),
                        ]),

                        TextEntry::make('deskripsi')
                            ->label('Deskripsi')
                            ->placeholder('-')
                            ->columnSpanFull(),

                        ImageEntry::make('bukti')
                            ->label('Bukti'),
                    ]),

                Section::make('Tindak Lanjut')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state) => match ($state) {
                                'proses' => 'Dalam Proses',
                                'selesai' => 'Selesai',
                                'batal' => 'Dibatalkan',
                                default => $state,
                            })
                            ->color(fn (string $state) => match ($state) {
                                'proses' => 'warning',
                                'selesai' => 'success',
                                'batal' => 'gray',
                                default => 'gray',
                            }),

                        TextEntry::make('tindak_lanjut')
                            ->label('Tindak Lanjut')
                            ->placeholder('-'),

                        TextEntry::make('catatan')
                            ->label('Catatan')
                            ->placeholder('-'),
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
