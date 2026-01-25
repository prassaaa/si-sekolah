<?php

namespace App\Filament\Resources\KenaikanKelass\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KenaikanKelasInfolist
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

                Section::make('Data Kelas')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('kelasAsal.nama')
                                ->label('Kelas Asal')
                                ->formatStateUsing(fn ($state, $record) => $record->kelasAsal?->tahunAjaran?->nama.' - '.$state),
                            TextEntry::make('kelasTujuan.nama')
                                ->label('Kelas Tujuan')
                                ->formatStateUsing(fn ($state, $record) => $record->kelasTujuan ? $record->kelasTujuan?->tahunAjaran?->nama.' - '.$state : '-')
                                ->placeholder('-'),
                        ]),
                    ]),

                Section::make('Status & Nilai')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn (string $state) => match ($state) {
                                    'naik' => 'Naik Kelas',
                                    'tinggal' => 'Tinggal Kelas',
                                    'mutasi_keluar' => 'Mutasi Keluar',
                                    'pending' => 'Pending',
                                    default => $state,
                                })
                                ->color(fn (string $state) => match ($state) {
                                    'naik' => 'success',
                                    'tinggal' => 'danger',
                                    'mutasi_keluar' => 'warning',
                                    'pending' => 'gray',
                                    default => 'gray',
                                }),

                            TextEntry::make('nilai_rata_rata')
                                ->label('Nilai Rata-rata')
                                ->placeholder('-'),

                            TextEntry::make('peringkat')
                                ->label('Peringkat')
                                ->placeholder('-'),
                        ]),
                    ]),

                Section::make('Keputusan')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('tanggal_keputusan')
                                ->label('Tanggal Keputusan')
                                ->date('d M Y')
                                ->placeholder('-'),
                            TextEntry::make('penyetuju.nama')
                                ->label('Disetujui Oleh')
                                ->placeholder('-'),
                        ]),

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
