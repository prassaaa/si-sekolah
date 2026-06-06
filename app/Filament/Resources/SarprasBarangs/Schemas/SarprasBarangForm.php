<?php

namespace App\Filament\Resources\SarprasBarangs\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SarprasBarangForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identifikasi Barang')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('kode_inventaris')
                                ->label('Kode Inventaris')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(50)
                                ->placeholder('Contoh: INV-2024-001'),
                            TextInput::make('nama')
                                ->label('Nama Barang')
                                ->required()
                                ->maxLength(255),
                        ]),
                        Grid::make(2)->schema([
                            Select::make('sarpras_kategori_id')
                                ->label('Kategori')
                                ->relationship('kategori', 'nama')
                                ->required()
                                ->searchable()
                                ->preload(),
                            Select::make('ruangan_id')
                                ->label('Ruangan / Lokasi')
                                ->relationship('ruangan', 'nama')
                                ->nullable()
                                ->searchable()
                                ->preload(),
                        ]),
                        Select::make('tipe')
                            ->label('Tipe')
                            ->options([
                                'aset' => 'Aset (Barang Tahan Lama)',
                                'bahan' => 'Bahan (Habis Pakai)',
                            ])
                            ->required(),
                    ]),

                Section::make('Detail Fisik')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('merk')
                                ->label('Merk / Merek')
                                ->maxLength(100),
                            Select::make('kondisi')
                                ->label('Kondisi')
                                ->options([
                                    'baik' => 'Baik',
                                    'rusak_ringan' => 'Rusak Ringan',
                                    'rusak_berat' => 'Rusak Berat',
                                ])
                                ->required(),
                        ]),
                        Textarea::make('spesifikasi')
                            ->label('Spesifikasi')
                            ->rows(3)
                            ->columnSpanFull(),
                        FileUpload::make('foto')
                            ->label('Foto')
                            ->image()
                            ->nullable()
                            ->directory('sarpras/foto')
                            ->columnSpanFull(),
                    ]),

                Section::make('Status & Pengadaan')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'tersedia' => 'Tersedia',
                                    'dipinjam' => 'Dipinjam',
                                    'perbaikan' => 'Perbaikan',
                                    'dihapus' => 'Dihapus',
                                ]),
                            Select::make('sumber_dana')
                                ->label('Sumber Dana')
                                ->options([
                                    'bos' => 'BOS',
                                    'komite' => 'Komite',
                                    'yayasan' => 'Yayasan',
                                    'hibah' => 'Hibah',
                                    'pribadi' => 'Pribadi',
                                    'lainnya' => 'Lainnya',
                                ]),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('tahun_perolehan')
                                ->label('Tahun Perolehan')
                                ->numeric()
                                ->minValue(2000)
                                ->maxValue(now()->year + 1)
                                ->placeholder((string) now()->year),
                            TextInput::make('harga_perolehan')
                                ->label('Harga Perolehan (Rp)')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->prefix('Rp'),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('jumlah')
                                ->label('Jumlah')
                                ->numeric()
                                ->minValue(0)
                                ->default(1),
                            TextInput::make('satuan')
                                ->label('Satuan')
                                ->default('unit')
                                ->maxLength(50),
                        ]),
                    ]),

                Section::make('Penyusutan')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('metode_susut')
                                ->label('Metode Penyusutan')
                                ->options([
                                    'tanpa' => 'Tanpa Penyusutan',
                                    'garis_lurus' => 'Garis Lurus',
                                    'saldo_menurun' => 'Saldo Menurun',
                                ])
                                ->default('tanpa'),
                            DatePicker::make('tanggal_perolehan')
                                ->label('Tanggal Perolehan')
                                ->nullable(),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('umur_ekonomis_bulan')
                                ->label('Umur Ekonomis (bulan)')
                                ->numeric()
                                ->minValue(1)
                                ->nullable()
                                ->helperText('Kosongkan untuk memakai default kategori.'),
                            TextInput::make('nilai_residu')
                                ->label('Nilai Residu (Rp)')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->prefix('Rp'),
                        ]),
                    ]),

                Section::make('Informasi Tambahan')
                    ->schema([
                        Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(3)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),
            ]);
    }
}
