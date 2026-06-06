<?php

namespace App\Filament\Resources\SarprasBarangs\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class SarprasBarangInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identifikasi Barang')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('kode_inventaris')
                                ->label('Kode Inventaris')
                                ->weight(FontWeight::Bold)
                                ->copyable(),
                            TextEntry::make('nama')
                                ->label('Nama Barang')
                                ->weight(FontWeight::Bold),
                        ]),
                        Grid::make(2)->schema([
                            TextEntry::make('kategori.nama')
                                ->label('Kategori')
                                ->badge()
                                ->color('info'),
                            TextEntry::make('ruangan.nama')
                                ->label('Ruangan / Lokasi')
                                ->placeholder('-'),
                        ]),
                        TextEntry::make('tipe')
                            ->label('Tipe')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'aset' => 'Aset (Barang Tahan Lama)',
                                'bahan' => 'Bahan (Habis Pakai)',
                                default => $state,
                            })
                            ->color(fn (string $state): string => match ($state) {
                                'aset' => 'primary',
                                'bahan' => 'warning',
                                default => 'gray',
                            }),
                    ]),

                Section::make('Detail Fisik')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('merk')
                                ->label('Merk / Merek')
                                ->placeholder('-'),
                            TextEntry::make('kondisi')
                                ->label('Kondisi')
                                ->badge()
                                ->formatStateUsing(fn ($record) => $record->kondisi_info['label'])
                                ->color(fn ($record) => $record->kondisi_info['color']),
                        ]),
                        TextEntry::make('spesifikasi')
                            ->label('Spesifikasi')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        ImageEntry::make('foto')
                            ->label('Foto')
                            ->height(200)
                            ->columnSpanFull(),
                    ]),

                Section::make('Status & Pengadaan')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn ($record) => $record->status_info['label'])
                                ->color(fn ($record) => $record->status_info['color']),
                            TextEntry::make('sumber_dana')
                                ->label('Sumber Dana')
                                ->badge()
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'bos' => 'BOS',
                                    'komite' => 'Komite',
                                    'yayasan' => 'Yayasan',
                                    'hibah' => 'Hibah',
                                    'pribadi' => 'Pribadi',
                                    'lainnya' => 'Lainnya',
                                    default => $state,
                                })
                                ->placeholder('-'),
                        ]),
                        Grid::make(3)->schema([
                            TextEntry::make('tahun_perolehan')
                                ->label('Tahun Perolehan')
                                ->placeholder('-'),
                            TextEntry::make('harga_perolehan')
                                ->label('Harga Perolehan')
                                ->money('IDR')
                                ->placeholder('-'),
                            TextEntry::make('jumlah')
                                ->label('Jumlah')
                                ->suffix(fn ($record) => ' '.$record->satuan),
                        ]),
                    ]),

                Section::make('Informasi Tambahan')
                    ->schema([
                        TextEntry::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        IconEntry::make('is_active')
                            ->label('Aktif')
                            ->boolean(),
                    ]),

                Section::make('Informasi Sistem')
                    ->collapsed()
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('created_at')
                                ->label('Dibuat')
                                ->dateTime('d M Y H:i'),
                            TextEntry::make('updated_at')
                                ->label('Diperbarui')
                                ->dateTime('d M Y H:i'),
                            TextEntry::make('deleted_at')
                                ->label('Dihapus')
                                ->dateTime('d M Y H:i')
                                ->placeholder('-'),
                        ]),
                    ]),
            ]);
    }
}
