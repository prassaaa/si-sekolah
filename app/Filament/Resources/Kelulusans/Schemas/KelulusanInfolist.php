<?php

namespace App\Filament\Resources\Kelulusans\Schemas;

use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class KelulusanInfolist
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
                            TextEntry::make('tahunAjaran.nama')
                                ->label('Tahun Ajaran'),
                        ]),
                    ]),

                Section::make('Data Kelulusan')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('tanggal_lulus')
                                ->label('Tanggal Lulus')
                                ->date('d M Y'),

                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn (string $state) => match ($state) {
                                    'lulus' => 'Lulus',
                                    'tidak_lulus' => 'Tidak Lulus',
                                    'pending' => 'Pending',
                                    default => $state,
                                })
                                ->color(fn (string $state) => match ($state) {
                                    'lulus' => 'success',
                                    'tidak_lulus' => 'danger',
                                    'pending' => 'gray',
                                    default => 'gray',
                                }),

                            TextEntry::make('predikat')
                                ->label('Predikat')
                                ->badge()
                                ->formatStateUsing(fn (?string $state) => match ($state) {
                                    'sangat_baik' => 'Sangat Baik',
                                    'baik' => 'Baik',
                                    'cukup' => 'Cukup',
                                    'kurang' => 'Kurang',
                                    default => $state ?? '-',
                                })
                                ->color(fn (?string $state) => match ($state) {
                                    'sangat_baik' => 'success',
                                    'baik' => 'info',
                                    'cukup' => 'warning',
                                    'kurang' => 'danger',
                                    default => 'gray',
                                }),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('nomor_ijazah')
                                ->label('Nomor Ijazah')
                                ->placeholder('-'),
                            TextEntry::make('nomor_skhun')
                                ->label('Nomor SKHUN')
                                ->placeholder('-'),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('nilai_akhir')
                                ->label('Nilai Akhir')
                                ->placeholder('-'),
                            TextEntry::make('tujuan_sekolah')
                                ->label('Tujuan Sekolah')
                                ->placeholder('-'),
                        ]),
                    ]),

                Section::make('Keputusan')
                    ->schema([
                        TextEntry::make('penyetuju.nama')
                            ->label('Disetujui Oleh')
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
