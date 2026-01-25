<?php

namespace App\Filament\Resources\Konselings\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KonselingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Siswa & Konselor')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('siswa.nama')
                                ->label('Nama Siswa'),
                            TextEntry::make('siswa.nisn')
                                ->label('NISN'),
                            TextEntry::make('konselor.nama')
                                ->label('Konselor'),
                        ]),
                        TextEntry::make('semester.nama')
                            ->label('Semester')
                            ->formatStateUsing(fn ($state, $record) => $record->semester?->tahunAjaran?->nama.' - '.$state),
                    ]),

                Section::make('Jadwal Konseling')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('tanggal')
                                ->label('Tanggal')
                                ->date('d M Y'),
                            TextEntry::make('waktu_mulai')
                                ->label('Waktu Mulai')
                                ->time('H:i'),
                            TextEntry::make('waktu_selesai')
                                ->label('Waktu Selesai')
                                ->time('H:i')
                                ->placeholder('-'),
                        ]),
                    ]),

                Section::make('Detail Konseling')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('jenis')
                                ->label('Jenis')
                                ->badge()
                                ->formatStateUsing(fn (string $state) => match ($state) {
                                    'individu' => 'Individu',
                                    'kelompok' => 'Kelompok',
                                    'keluarga' => 'Keluarga',
                                    default => $state,
                                }),

                            TextEntry::make('kategori')
                                ->label('Kategori')
                                ->badge()
                                ->formatStateUsing(fn (string $state) => match ($state) {
                                    'akademik' => 'Akademik',
                                    'pribadi' => 'Pribadi',
                                    'sosial' => 'Sosial',
                                    'karir' => 'Karir',
                                    'lainnya' => 'Lainnya',
                                    default => $state,
                                })
                                ->color(fn (string $state) => match ($state) {
                                    'akademik' => 'info',
                                    'pribadi' => 'warning',
                                    'sosial' => 'success',
                                    'karir' => 'primary',
                                    default => 'gray',
                                }),

                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn (string $state) => match ($state) {
                                    'dijadwalkan' => 'Dijadwalkan',
                                    'berlangsung' => 'Berlangsung',
                                    'selesai' => 'Selesai',
                                    'batal' => 'Dibatalkan',
                                    default => $state,
                                })
                                ->color(fn (string $state) => match ($state) {
                                    'dijadwalkan' => 'info',
                                    'berlangsung' => 'warning',
                                    'selesai' => 'success',
                                    'batal' => 'danger',
                                    default => 'gray',
                                }),
                        ]),

                        TextEntry::make('permasalahan')
                            ->label('Permasalahan'),

                        TextEntry::make('hasil_konseling')
                            ->label('Hasil Konseling')
                            ->placeholder('-'),

                        TextEntry::make('rekomendasi')
                            ->label('Rekomendasi')
                            ->placeholder('-'),
                    ]),

                Section::make('Tindak Lanjut')
                    ->schema([
                        Grid::make(3)->schema([
                            IconEntry::make('perlu_tindak_lanjut')
                                ->label('Perlu Tindak Lanjut')
                                ->boolean(),
                            TextEntry::make('tanggal_tindak_lanjut')
                                ->label('Tanggal Tindak Lanjut')
                                ->date('d M Y')
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
