<?php

namespace App\Filament\Resources\JenisPembayarans\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JenisPembayaranInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Jenis Pembayaran')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('kategoriPembayaran.nama')
                                ->label('Kategori'),
                            TextEntry::make('tahunAjaran.nama')
                                ->label('Tahun Ajaran'),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('kode')
                                ->label('Kode'),
                            TextEntry::make('nama')
                                ->label('Nama'),
                        ]),

                        Grid::make(3)->schema([
                            TextEntry::make('nominal')
                                ->label('Nominal')
                                ->money('IDR'),
                            TextEntry::make('jenis')
                                ->label('Jenis')
                                ->badge()
                                ->formatStateUsing(fn (string $state) => match ($state) {
                                    'bulanan' => 'Bulanan',
                                    'tahunan' => 'Tahunan',
                                    'sekali_bayar' => 'Sekali Bayar',
                                    'insidental' => 'Insidental',
                                    default => $state,
                                }),
                            TextEntry::make('tanggal_jatuh_tempo')
                                ->label('Jatuh Tempo')
                                ->date('d M Y')
                                ->placeholder('-'),
                        ]),

                        Grid::make(2)->schema([
                            IconEntry::make('is_active')
                                ->label('Aktif')
                                ->boolean(),
                            TextEntry::make('deskripsi')
                                ->label('Deskripsi')
                                ->placeholder('-'),
                        ]),
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
