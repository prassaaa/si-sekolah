<?php

namespace App\Filament\Resources\SarprasPeminjamans\Schemas;

use App\Models\Pegawai;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SarprasPeminjamanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Nomor & Status')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('nomor')->label('Nomor')->copyable(),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn ($record) => $record->status_info['color'])
                        ->formatStateUsing(fn ($record) => $record->status_info['label']),
                ]),
            ]),

            Section::make('Barang')->schema([
                TextEntry::make('barang.kode_inventaris')->label('Kode Inventaris'),
                TextEntry::make('barang.nama')->label('Nama Barang'),
                TextEntry::make('jumlah')->label('Jumlah'),
            ]),

            Section::make('Peminjam')->schema([
                TextEntry::make('peminjam_type')
                    ->label('Tipe Peminjam')
                    ->formatStateUsing(fn ($state) => $state === Pegawai::class ? 'Pegawai' : 'Siswa'),
                TextEntry::make('peminjam.nama')->label('Nama Peminjam'),
            ]),

            Section::make('Tanggal & Kondisi')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('tanggal_pinjam')->label('Tanggal Pinjam')->date('d M Y'),
                    TextEntry::make('tanggal_harus_kembali')->label('Harus Kembali')->date('d M Y'),
                    TextEntry::make('tanggal_kembali')->label('Tanggal Kembali')->date('d M Y')->placeholder('-'),
                    TextEntry::make('kondisi_pinjam')->label('Kondisi Pinjam'),
                    TextEntry::make('kondisi_kembali')->label('Kondisi Kembali')->placeholder('-'),
                ]),
            ]),

            Section::make('Denda Keterlambatan')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('hari_telat')
                        ->label('Hari Terlambat')
                        ->suffix(' hari')
                        ->placeholder('0'),
                    TextEntry::make('denda')
                        ->label('Denda')
                        ->money('IDR')
                        ->placeholder('Rp 0'),
                ]),
            ]),

            Section::make('Petugas & Catatan')->schema([
                TextEntry::make('petugas.nama')->label('Petugas')->placeholder('-'),
                TextEntry::make('catatan')->label('Catatan')->placeholder('-')->columnSpanFull(),
            ]),
        ]);
    }
}
