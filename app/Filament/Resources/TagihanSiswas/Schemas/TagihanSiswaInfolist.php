<?php

namespace App\Filament\Resources\TagihanSiswas\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TagihanSiswaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Tagihan')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('nomor_tagihan')
                                ->label('Nomor Tagihan')
                                ->copyable(),
                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->color(fn (string $state) => match ($state) {
                                    'belum_bayar' => 'danger',
                                    'sebagian' => 'warning',
                                    'lunas' => 'success',
                                    'batal' => 'gray',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn (string $state) => match ($state) {
                                    'belum_bayar' => 'Belum Bayar',
                                    'sebagian' => 'Sebagian',
                                    'lunas' => 'Lunas',
                                    'batal' => 'Batal',
                                    default => $state,
                                }),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('siswa.nama')
                                ->label('Siswa'),
                            TextEntry::make('siswa.nisn')
                                ->label('NISN'),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('jenisPembayaran.nama')
                                ->label('Jenis Pembayaran'),
                            TextEntry::make('semester.nama')
                                ->label('Semester')
                                ->placeholder('-'),
                        ]),
                    ]),

                Section::make('Nominal')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('nominal')
                                ->label('Nominal')
                                ->money('IDR'),
                            TextEntry::make('diskon')
                                ->label('Diskon')
                                ->money('IDR'),
                            TextEntry::make('total_tagihan')
                                ->label('Total Tagihan')
                                ->money('IDR')
                                ->weight('bold'),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('total_terbayar')
                                ->label('Total Terbayar')
                                ->money('IDR')
                                ->color('success'),
                            TextEntry::make('sisa_tagihan')
                                ->label('Sisa Tagihan')
                                ->money('IDR')
                                ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
                        ]),
                    ]),

                Section::make('Tanggal')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('tanggal_tagihan')
                                ->label('Tanggal Tagihan')
                                ->date('d M Y'),
                            TextEntry::make('tanggal_jatuh_tempo')
                                ->label('Jatuh Tempo')
                                ->date('d M Y')
                                ->placeholder('-'),
                        ]),

                        TextEntry::make('keterangan')
                            ->label('Keterangan')
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
