<?php

namespace App\Filament\Resources\MataPelajarans\Schemas;

use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class MataPelajaranInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Mata Pelajaran')
                    ->icon('heroicon-o-book-open')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('kode')
                                ->label('Kode')
                                ->weight(FontWeight::Bold)
                                ->copyable(),
                            TextEntry::make('nama')
                                ->label('Nama Mata Pelajaran')
                                ->weight(FontWeight::Bold),
                            TextEntry::make('singkatan')
                                ->label('Singkatan')
                                ->placeholder('-'),
                        ]),
                        Grid::make(3)->schema([
                            TextEntry::make('kelompok')
                                ->label('Kelompok')
                                ->badge()
                                ->color('info')
                                ->placeholder('-'),
                            TextEntry::make('jenjang')
                                ->label('Jenjang')
                                ->badge()
                                ->color('success')
                                ->placeholder('-'),
                            TextEntry::make('urutan')
                                ->label('Urutan'),
                        ]),
                        Grid::make(2)->schema([
                            TextEntry::make('jam_per_minggu')
                                ->label('Jam/Minggu')
                                ->suffix(' jam'),
                            TextEntry::make('kkm')
                                ->label('KKM')
                                ->badge()
                                ->color('warning'),
                        ]),
                        TextEntry::make('is_active')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Non-Aktif')
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                        TextEntry::make('deskripsi')
                            ->label('Deskripsi')
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
