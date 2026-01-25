<?php

namespace App\Filament\Resources\TahunAjarans\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class TahunAjaranInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Tahun Ajaran')
                    ->icon('heroicon-o-calendar-date-range')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('kode')
                                ->label('Kode')
                                ->weight(FontWeight::Bold)
                                ->copyable(),
                            TextEntry::make('nama')
                                ->label('Nama Tahun Ajaran')
                                ->weight(FontWeight::Bold),
                        ]),
                        Grid::make(2)->schema([
                            TextEntry::make('tanggal_mulai')
                                ->label('Tanggal Mulai')
                                ->date('d F Y'),
                            TextEntry::make('tanggal_selesai')
                                ->label('Tanggal Selesai')
                                ->date('d F Y'),
                        ]),
                        TextEntry::make('is_active')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Non-Aktif')
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
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
