<?php

namespace App\Filament\Resources\SarprasPemeliharaans\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SarprasPemeliharaanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Pemeliharaan')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('nomor')->label('Nomor'),
                    TextEntry::make('jenis')
                        ->label('Jenis')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => match ($state) {
                            'rutin' => 'Rutin',
                            'perbaikan' => 'Perbaikan',
                            'kalibrasi' => 'Kalibrasi',
                            default => $state,
                        }),
                ]),
                Grid::make(2)->schema([
                    TextEntry::make('barang.kode_inventaris')->label('Kode Inventaris'),
                    TextEntry::make('barang.nama')->label('Nama Barang'),
                ]),
                Grid::make(2)->schema([
                    TextEntry::make('tanggal')->label('Tanggal Mulai')->date('d M Y'),
                    TextEntry::make('tanggal_selesai')->label('Tanggal Selesai')->date('d M Y')->placeholder('-'),
                ]),
            ]),

            Section::make('Detail Masalah & Tindakan')->schema([
                TextEntry::make('deskripsi_masalah')->label('Deskripsi Masalah')->columnSpanFull(),
                TextEntry::make('tindakan')->label('Tindakan')->columnSpanFull()->placeholder('-'),
            ]),

            Section::make('Pelaksana & Biaya')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('pelaksana')
                        ->label('Pelaksana')
                        ->formatStateUsing(fn (string $state) => match ($state) {
                            'internal' => 'Internal',
                            'vendor' => 'Vendor / Pihak Ketiga',
                            default => $state,
                        }),
                    TextEntry::make('nama_vendor')->label('Nama Vendor')->placeholder('-'),
                ]),
                TextEntry::make('biaya')
                    ->label('Biaya')
                    ->money('IDR'),
            ]),

            Section::make('Kondisi & Status')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn ($record) => $record->status_info['color'])
                        ->formatStateUsing(fn ($record) => $record->status_info['label']),
                    TextEntry::make('kondisi_sebelum')
                        ->label('Kondisi Sebelum')
                        ->formatStateUsing(fn (?string $state) => match ($state) {
                            'baik' => 'Baik',
                            'rusak_ringan' => 'Rusak Ringan',
                            'rusak_berat' => 'Rusak Berat',
                            default => '-',
                        }),
                    TextEntry::make('kondisi_sesudah')
                        ->label('Kondisi Sesudah')
                        ->formatStateUsing(fn (?string $state) => match ($state) {
                            'baik' => 'Baik',
                            'rusak_ringan' => 'Rusak Ringan',
                            'rusak_berat' => 'Rusak Berat',
                            default => '-',
                        }),
                ]),
            ]),

            Section::make('Audit')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('pencatat.name')->label('Dicatat Oleh')->placeholder('-'),
                    TextEntry::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
                ]),
            ])->collapsible(),
        ]);
    }
}
