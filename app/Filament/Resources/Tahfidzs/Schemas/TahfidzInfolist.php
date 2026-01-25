<?php

namespace App\Filament\Resources\Tahfidzs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TahfidzInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Siswa & Semester')
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

                        TextEntry::make('penguji.nama')
                            ->label('Penguji')
                            ->placeholder('-'),
                    ]),

                Section::make('Data Hafalan')
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('surah')
                                ->label('Surah'),

                            TextEntry::make('ayat_mulai')
                                ->label('Ayat Mulai'),

                            TextEntry::make('ayat_selesai')
                                ->label('Ayat Selesai'),

                            TextEntry::make('jumlah_ayat')
                                ->label('Jumlah Ayat')
                                ->suffix(' ayat'),
                        ]),

                        TextEntry::make('juz')
                            ->label('Juz')
                            ->placeholder('-'),

                        TextEntry::make('hafalan')
                            ->label('Hafalan')
                            ->badge()
                            ->color('info'),
                    ]),

                Section::make('Penilaian')
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('tanggal')
                                ->label('Tanggal')
                                ->date('d M Y'),

                            TextEntry::make('jenis')
                                ->label('Jenis')
                                ->badge()
                                ->formatStateUsing(fn (string $state) => match ($state) {
                                    'setoran' => 'Setoran',
                                    'murojaah' => 'Murojaah',
                                    'ujian' => 'Ujian',
                                    default => $state,
                                })
                                ->color(fn (string $state) => match ($state) {
                                    'setoran' => 'info',
                                    'murojaah' => 'warning',
                                    'ujian' => 'success',
                                    default => 'gray',
                                }),

                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn (string $state) => match ($state) {
                                    'lulus' => 'Lulus',
                                    'mengulang' => 'Mengulang',
                                    'pending' => 'Pending',
                                    default => $state,
                                })
                                ->color(fn (string $state) => match ($state) {
                                    'lulus' => 'success',
                                    'mengulang' => 'danger',
                                    'pending' => 'warning',
                                    default => 'gray',
                                }),

                            TextEntry::make('nilai')
                                ->label('Nilai')
                                ->suffix('/100')
                                ->placeholder('-'),
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
