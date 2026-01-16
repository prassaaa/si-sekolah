<?php

namespace App\Filament\Resources\Pembayarans\Schemas;

use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PembayaranInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Pembayaran')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('nomor_transaksi')
                                ->label('Nomor Transaksi')
                                ->copyable(),
                            TextEntry::make('tanggal_bayar')
                                ->label('Tanggal Bayar')
                                ->date('d M Y'),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('tagihanSiswa.nomor_tagihan')
                                ->label('Nomor Tagihan'),
                            TextEntry::make('tagihanSiswa.siswa.nama')
                                ->label('Siswa'),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('tagihanSiswa.jenisPembayaran.nama')
                                ->label('Jenis Pembayaran'),
                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->color(fn (string $state) => match ($state) {
                                    'pending' => 'warning',
                                    'berhasil' => 'success',
                                    'gagal' => 'danger',
                                    'batal' => 'gray',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn (string $state) => match ($state) {
                                    'pending' => 'Pending',
                                    'berhasil' => 'Berhasil',
                                    'gagal' => 'Gagal',
                                    'batal' => 'Batal',
                                    default => $state,
                                }),
                        ]),
                    ]),

                Section::make('Nominal & Metode')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('jumlah_bayar')
                                ->label('Jumlah Bayar')
                                ->money('IDR')
                                ->weight('bold'),
                            TextEntry::make('metode_pembayaran')
                                ->label('Metode Pembayaran')
                                ->badge()
                                ->formatStateUsing(fn (string $state) => match ($state) {
                                    'tunai' => 'Tunai',
                                    'transfer' => 'Transfer Bank',
                                    'qris' => 'QRIS',
                                    'virtual_account' => 'Virtual Account',
                                    'lainnya' => 'Lainnya',
                                    default => $state,
                                }),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('referensi_pembayaran')
                                ->label('Referensi Pembayaran')
                                ->placeholder('-'),
                            TextEntry::make('penerima.name')
                                ->label('Diterima Oleh')
                                ->placeholder('-'),
                        ]),

                        TextEntry::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('-'),
                    ]),

                Section::make('Sisa Tagihan')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('tagihanSiswa.total_tagihan')
                                ->label('Total Tagihan')
                                ->money('IDR'),
                            TextEntry::make('tagihanSiswa.total_terbayar')
                                ->label('Total Terbayar')
                                ->money('IDR')
                                ->color('success'),
                            TextEntry::make('tagihanSiswa.sisa_tagihan')
                                ->label('Sisa Tagihan')
                                ->money('IDR')
                                ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
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
