<?php

namespace App\Filament\Resources\Prestasis\Schemas;

use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PrestasiInfolist
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

                Section::make('Data Prestasi')
                    ->schema([
                        TextEntry::make('nama_prestasi')
                            ->label('Nama Prestasi'),

                        Grid::make(3)->schema([
                            TextEntry::make('tingkat')
                                ->label('Tingkat')
                                ->badge()
                                ->formatStateUsing(fn (string $state) => match ($state) {
                                    'sekolah' => 'Sekolah',
                                    'kecamatan' => 'Kecamatan',
                                    'kabupaten' => 'Kabupaten',
                                    'provinsi' => 'Provinsi',
                                    'nasional' => 'Nasional',
                                    'internasional' => 'Internasional',
                                    default => $state,
                                })
                                ->color(fn (string $state) => match ($state) {
                                    'nasional', 'internasional' => 'success',
                                    'provinsi' => 'warning',
                                    default => 'info',
                                }),

                            TextEntry::make('jenis')
                                ->label('Jenis')
                                ->badge(),

                            TextEntry::make('peringkat')
                                ->label('Peringkat')
                                ->badge()
                                ->color('success'),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('penyelenggara')
                                ->label('Penyelenggara')
                                ->placeholder('-'),
                            TextEntry::make('tanggal')
                                ->label('Tanggal')
                                ->date('d M Y'),
                        ]),

                        ImageEntry::make('bukti')
                            ->label('Bukti/Sertifikat'),

                        TextEntry::make('keterangan')
                            ->label('Keterangan')
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
