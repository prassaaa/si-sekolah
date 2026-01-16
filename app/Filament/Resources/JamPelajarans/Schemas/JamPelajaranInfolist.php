<?php

namespace App\Filament\Resources\JamPelajarans\Schemas;

use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class JamPelajaranInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Jam Pelajaran')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('jam_ke')
                                ->label('Jam Ke')
                                ->weight(FontWeight::Bold)
                                ->formatStateUsing(fn (int $state): string => "Jam ke-{$state}"),
                            TextEntry::make('jenis')
                                ->label('Jenis')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'Reguler' => 'primary',
                                    'Istirahat' => 'warning',
                                    'Upacara' => 'info',
                                    'Ekstrakurikuler' => 'success',
                                    default => 'gray',
                                }),
                        ]),
                        Grid::make(3)->schema([
                            TextEntry::make('waktu_mulai')
                                ->label('Waktu Mulai')
                                ->time('H:i'),
                            TextEntry::make('waktu_selesai')
                                ->label('Waktu Selesai')
                                ->time('H:i'),
                            TextEntry::make('durasi')
                                ->label('Durasi')
                                ->suffix(' menit'),
                        ]),
                        TextEntry::make('rentang_waktu')
                            ->label('Rentang Waktu')
                            ->weight(FontWeight::Bold),
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
