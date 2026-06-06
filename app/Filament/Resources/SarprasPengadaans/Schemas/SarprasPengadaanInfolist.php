<?php

namespace App\Filament\Resources\SarprasPengadaans\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SarprasPengadaanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Pengadaan')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('nomor')
                        ->label('Nomor'),

                    TextEntry::make('tanggal')
                        ->label('Tanggal')
                        ->date('d M Y'),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn (string $state) => match ($state) {
                            'draft' => 'gray',
                            'disetujui' => 'info',
                            'diterima' => 'success',
                            'batal' => 'danger',
                            default => 'gray',
                        })
                        ->formatStateUsing(fn (string $state) => match ($state) {
                            'draft' => 'Draft',
                            'disetujui' => 'Disetujui',
                            'diterima' => 'Diterima',
                            'batal' => 'Batal',
                            default => $state,
                        }),
                ]),

                Grid::make(3)->schema([
                    TextEntry::make('sumber_dana')
                        ->label('Sumber Dana')
                        ->formatStateUsing(fn (string $state) => match ($state) {
                            'bos' => 'BOS',
                            'komite' => 'Komite',
                            'yayasan' => 'Yayasan',
                            'hibah' => 'Hibah',
                            'pribadi' => 'Pribadi',
                            'lainnya' => 'Lainnya',
                            default => $state,
                        }),

                    TextEntry::make('penyedia')
                        ->label('Penyedia / Supplier')
                        ->placeholder('-'),

                    TextEntry::make('total_biaya')
                        ->label('Total Biaya')
                        ->money('IDR'),
                ]),

                TextEntry::make('pembuat.name')
                    ->label('Dibuat Oleh')
                    ->placeholder('-'),

                TextEntry::make('keterangan')
                    ->label('Keterangan')
                    ->placeholder('-'),
            ]),

            Section::make('Item Pengadaan')->schema([
                RepeatableEntry::make('items')
                    ->label('Daftar Barang')
                    ->schema([
                        Grid::make(5)->schema([
                            TextEntry::make('nama_barang')
                                ->label('Nama Barang'),

                            TextEntry::make('kategori.nama')
                                ->label('Kategori'),

                            TextEntry::make('jumlah')
                                ->label('Jumlah'),

                            TextEntry::make('satuan')
                                ->label('Satuan'),

                            TextEntry::make('harga_satuan')
                                ->label('Harga Satuan')
                                ->money('IDR'),

                            TextEntry::make('subtotal')
                                ->label('Subtotal')
                                ->money('IDR'),
                        ]),
                    ]),
            ]),
        ]);
    }
}
