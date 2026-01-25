<?php

namespace App\Filament\Resources\Kelases\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class KelasInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kelas')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('tahunAjaran.nama')
                                ->label('Tahun Ajaran'),
                            TextEntry::make('nama')
                                ->label('Nama Kelas')
                                ->weight(FontWeight::Bold)
                                ->size('lg'),
                            TextEntry::make('nama_lengkap')
                                ->label('Nama Lengkap'),
                        ]),
                        Grid::make(3)->schema([
                            TextEntry::make('tingkat')
                                ->label('Tingkat')
                                ->formatStateUsing(fn (int $state): string => "Kelas $state")
                                ->badge()
                                ->color('info'),
                            TextEntry::make('jurusan')
                                ->label('Jurusan')
                                ->placeholder('-'),
                            TextEntry::make('kapasitas')
                                ->label('Kapasitas')
                                ->suffix(' siswa'),
                        ]),
                    ]),

                Section::make('Detail Kelas')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('waliKelas.nama')
                                ->label('Wali Kelas')
                                ->placeholder('Belum ditentukan'),
                            TextEntry::make('ruangan')
                                ->label('Ruangan')
                                ->placeholder('-'),
                            TextEntry::make('jumlah_siswa')
                                ->label('Jumlah Siswa')
                                ->suffix(' siswa')
                                ->badge()
                                ->color('success'),
                        ]),
                        Grid::make(2)->schema([
                            TextEntry::make('urutan')
                                ->label('Urutan'),
                            TextEntry::make('is_active')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Non-Aktif')
                                ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                        ]),
                        TextEntry::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Informasi Sistem')
                    ->collapsed()
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('created_at')
                                ->label('Dibuat')
                                ->dateTime('d M Y H:i'),
                            TextEntry::make('updated_at')
                                ->label('Diperbarui')
                                ->dateTime('d M Y H:i'),
                        ]),
                    ]),
            ]);
    }
}
