<?php

namespace App\Filament\Resources\JenisPembayarans\Schemas;

use App\Models\KategoriPembayaran;
use App\Models\TahunAjaran;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JenisPembayaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Jenis Pembayaran')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('kategori_pembayaran_id')
                                ->label('Kategori')
                                ->relationship('kategoriPembayaran', 'nama')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->getOptionLabelFromRecordUsing(fn (KategoriPembayaran $record) => "{$record->kode} - {$record->nama}"),

                            Select::make('tahun_ajaran_id')
                                ->label('Tahun Ajaran')
                                ->relationship('tahunAjaran', 'nama')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->getOptionLabelFromRecordUsing(fn (TahunAjaran $record) => $record->nama),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('kode')
                                ->label('Kode')
                                ->required()
                                ->maxLength(20),

                            TextInput::make('nama')
                                ->label('Nama')
                                ->required()
                                ->maxLength(100),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('nominal')
                                ->label('Nominal')
                                ->required()
                                ->numeric()
                                ->prefix('Rp')
                                ->minValue(0),

                            Select::make('jenis')
                                ->label('Jenis')
                                ->options([
                                    'bulanan' => 'Bulanan',
                                    'tahunan' => 'Tahunan',
                                    'sekali_bayar' => 'Sekali Bayar',
                                    'insidental' => 'Insidental',
                                ])
                                ->required()
                                ->default('bulanan'),
                        ]),

                        Grid::make(2)->schema([
                            DatePicker::make('tanggal_jatuh_tempo')
                                ->label('Tanggal Jatuh Tempo')
                                ->native(false)
                                ->helperText('Batas waktu pembayaran'),

                            Toggle::make('is_active')
                                ->label('Aktif')
                                ->default(true)
                                ->inline(false),
                        ]),

                        Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->rows(3),
                    ]),
            ]);
    }
}
