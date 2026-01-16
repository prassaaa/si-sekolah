<?php

namespace App\Filament\Resources\JadwalPelajarans\Schemas;

use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class JadwalPelajaranInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Jadwal Pelajaran')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('semester.nama')
                                ->label('Semester'),
                            TextEntry::make('kelas.nama')
                                ->label('Kelas')
                                ->badge()
                                ->color('success'),
                            TextEntry::make('hari')
                                ->label('Hari')
                                ->badge()
                                ->color('info'),
                        ]),
                        Grid::make(3)->schema([
                            TextEntry::make('jamPelajaran.nama')
                                ->label('Jam Pelajaran')
                                ->weight(FontWeight::Bold),
                            TextEntry::make('waktu')
                                ->label('Waktu'),
                            TextEntry::make('is_active')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Non-Aktif')
                                ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                        ]),
                    ]),

                Section::make('Detail Pelajaran')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('mataPelajaran.nama')
                                ->label('Mata Pelajaran')
                                ->weight(FontWeight::Bold)
                                ->size('lg'),
                            TextEntry::make('guru.nama')
                                ->label('Guru')
                                ->placeholder('Belum ditentukan'),
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
