<?php

namespace App\Filament\Resources\SarprasPengadaans\Schemas;

use App\Models\SarprasKategori;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SarprasPengadaanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Pengadaan')->schema([
                Grid::make(2)->schema([
                    TextInput::make('nomor')
                        ->label('Nomor')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Otomatis generated'),

                    DatePicker::make('tanggal')
                        ->label('Tanggal')
                        ->required()
                        ->default(now())
                        ->native(false),
                ]),

                Grid::make(2)->schema([
                    Select::make('sumber_dana')
                        ->label('Sumber Dana')
                        ->options([
                            'bos' => 'BOS',
                            'komite' => 'Komite',
                            'yayasan' => 'Yayasan',
                            'hibah' => 'Hibah',
                            'pribadi' => 'Pribadi',
                            'lainnya' => 'Lainnya',
                        ])
                        ->required()
                        ->searchable(),

                    TextInput::make('penyedia')
                        ->label('Penyedia / Supplier')
                        ->maxLength(255),
                ]),

                Grid::make(2)->schema([
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'draft' => 'Draft',
                            'disetujui' => 'Disetujui',
                            'diterima' => 'Diterima',
                            'batal' => 'Batal',
                        ])
                        ->required()
                        ->default('draft'),

                    Select::make('dibuat_oleh')
                        ->label('Dibuat Oleh')
                        ->options(fn () => User::query()->pluck('name', 'id')->toArray())
                        ->default(fn () => auth()->id())
                        ->searchable()
                        ->preload(),
                ]),

                Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->rows(3),
            ]),

            Section::make('Item Pengadaan')->schema([
                Repeater::make('items')
                    ->relationship('items')
                    ->label('Daftar Barang')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('nama_barang')
                                ->label('Nama Barang')
                                ->required()
                                ->maxLength(255),

                            Select::make('sarpras_kategori_id')
                                ->label('Kategori')
                                ->options(fn () => SarprasKategori::query()->active()->pluck('nama', 'id')->toArray())
                                ->required()
                                ->searchable()
                                ->preload(),
                        ]),

                        Grid::make(3)->schema([
                            TextInput::make('jumlah')
                                ->label('Jumlah')
                                ->required()
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->live(debounce: 500),

                            TextInput::make('satuan')
                                ->label('Satuan')
                                ->required()
                                ->default('unit')
                                ->maxLength(50),

                            TextInput::make('harga_satuan')
                                ->label('Harga Satuan')
                                ->required()
                                ->numeric()
                                ->minValue(0)
                                ->prefix('Rp')
                                ->live(debounce: 500),
                        ]),

                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->disabled()
                            ->dehydrated(false)
                            ->prefix('Rp')
                            ->formatStateUsing(function ($get) {
                                $jumlah = (float) ($get('jumlah') ?? 0);
                                $harga = (float) ($get('harga_satuan') ?? 0);

                                return number_format($jumlah * $harga, 0, ',', '.');
                            }),
                    ])
                    ->columns(1)
                    ->defaultItems(1)
                    ->addActionLabel('Tambah Barang')
                    ->reorderable(false)
                    ->collapsible(),
            ]),
        ]);
    }
}
