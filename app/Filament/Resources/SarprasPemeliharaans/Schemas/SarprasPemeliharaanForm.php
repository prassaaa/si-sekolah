<?php

namespace App\Filament\Resources\SarprasPemeliharaans\Schemas;

use App\Models\SarprasBarang;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SarprasPemeliharaanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Pemeliharaan')->schema([
                Grid::make(2)->schema([
                    Select::make('sarpras_barang_id')
                        ->label('Barang / Aset')
                        ->relationship('barang', 'nama')
                        ->getOptionLabelFromRecordUsing(
                            fn (SarprasBarang $record) => "{$record->kode_inventaris} - {$record->nama}"
                        )
                        ->searchable(['kode_inventaris', 'nama'])
                        ->preload()
                        ->required(),

                    Select::make('jenis')
                        ->label('Jenis Pemeliharaan')
                        ->options([
                            'rutin' => 'Rutin',
                            'perbaikan' => 'Perbaikan',
                            'kalibrasi' => 'Kalibrasi',
                        ])
                        ->required(),
                ]),

                Grid::make(2)->schema([
                    DatePicker::make('tanggal')
                        ->label('Tanggal Mulai')
                        ->required()
                        ->native(false)
                        ->default(now()),

                    DatePicker::make('tanggal_selesai')
                        ->label('Tanggal Selesai')
                        ->native(false)
                        ->afterOrEqual('tanggal'),
                ]),
            ]),

            Section::make('Detail Masalah & Tindakan')->schema([
                Textarea::make('deskripsi_masalah')
                    ->label('Deskripsi Masalah')
                    ->rows(3)
                    ->required(),

                Textarea::make('tindakan')
                    ->label('Tindakan yang Dilakukan')
                    ->rows(3),
            ]),

            Section::make('Pelaksana & Biaya')->schema([
                Grid::make(2)->schema([
                    Select::make('pelaksana')
                        ->label('Pelaksana')
                        ->options([
                            'internal' => 'Internal',
                            'vendor' => 'Vendor / Pihak Ketiga',
                        ])
                        ->required()
                        ->live()
                        ->default('internal'),

                    TextInput::make('nama_vendor')
                        ->label('Nama Vendor')
                        ->visible(fn ($get) => $get('pelaksana') === 'vendor')
                        ->maxLength(255),
                ]),

                TextInput::make('biaya')
                    ->label('Biaya (Rp)')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->prefix('Rp'),
            ]),

            Section::make('Kondisi & Status')->schema([
                Grid::make(2)->schema([
                    Select::make('kondisi_sebelum')
                        ->label('Kondisi Sebelum')
                        ->options([
                            'baik' => 'Baik',
                            'rusak_ringan' => 'Rusak Ringan',
                            'rusak_berat' => 'Rusak Berat',
                        ]),

                    Select::make('kondisi_sesudah')
                        ->label('Kondisi Sesudah')
                        ->options([
                            'baik' => 'Baik',
                            'rusak_ringan' => 'Rusak Ringan',
                            'rusak_berat' => 'Rusak Berat',
                        ]),
                ]),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'dijadwalkan' => 'Dijadwalkan',
                        'proses' => 'Proses',
                        'selesai' => 'Selesai',
                        'batal' => 'Batal',
                    ])
                    ->required()
                    ->default('dijadwalkan'),
            ]),
        ]);
    }
}
