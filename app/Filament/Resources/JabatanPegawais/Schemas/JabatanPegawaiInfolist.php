<?php

namespace App\Filament\Resources\JabatanPegawais\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JabatanPegawaiInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Jabatan')
                    ->schema([
                        TextEntry::make('kode')
                            ->label('Kode Jabatan')
                            ->badge(),
                        TextEntry::make('nama')
                            ->label('Nama Jabatan'),
                        TextEntry::make('jenis')
                            ->label('Jenis Jabatan')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Struktural' => 'success',
                                'Fungsional' => 'info',
                                default => 'gray',
                            }),
                        TextEntry::make('golongan')
                            ->label('Golongan')
                            ->badge()
                            ->color('warning')
                            ->default('-'),
                    ])
                    ->columns(2),

                Section::make('Kompensasi')
                    ->schema([
                        TextEntry::make('gaji_pokok')
                            ->label('Gaji Pokok')
                            ->money('IDR'),
                        TextEntry::make('tunjangan')
                            ->label('Tunjangan')
                            ->money('IDR'),
                        TextEntry::make('total_gaji')
                            ->label('Total Gaji')
                            ->money('IDR')
                            ->state(fn ($record) => $record->gaji_pokok + $record->tunjangan),
                    ])
                    ->columns(3),

                Section::make('Lainnya')
                    ->schema([
                        TextEntry::make('deskripsi')
                            ->label('Deskripsi')
                            ->default('-')
                            ->columnSpanFull(),
                        TextEntry::make('urutan')
                            ->label('Urutan'),
                        IconEntry::make('is_active')
                            ->label('Status Aktif')
                            ->boolean(),
                        TextEntry::make('pegawais_count')
                            ->label('Jumlah Pegawai')
                            ->state(fn ($record) => $record->pegawais()->count())
                            ->badge()
                            ->color('primary'),
                    ])
                    ->columns(3),

                Section::make('Informasi Waktu')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime('d F Y H:i'),
                        TextEntry::make('updated_at')
                            ->label('Diperbarui')
                            ->dateTime('d F Y H:i'),
                    ])
                    ->columns(2),
            ]);
    }
}
