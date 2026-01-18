<?php

namespace App\Filament\Resources\TagihanSiswas\Schemas;

use App\Models\JenisPembayaran;
use App\Models\Semester;
use App\Models\Siswa;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TagihanSiswaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Tagihan')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('siswa_id')
                                ->label('Siswa')
                                ->relationship('siswa', 'nama')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->getOptionLabelFromRecordUsing(fn (Siswa $record) => "{$record->nisn} - {$record->nama}"),

                            Select::make('jenis_pembayaran_id')
                                ->label('Jenis Pembayaran')
                                ->relationship('jenisPembayaran', 'nama')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set, ?string $state) {
                                    if ($state) {
                                        $jenisPembayaran = JenisPembayaran::find($state);
                                        if ($jenisPembayaran) {
                                            $set('nominal', $jenisPembayaran->nominal);
                                            $set('tanggal_jatuh_tempo', $jenisPembayaran->tanggal_jatuh_tempo);
                                        }
                                    }
                                })
                                ->getOptionLabelFromRecordUsing(fn (JenisPembayaran $record) => "{$record->kode} - {$record->nama}"),
                        ]),

                        Grid::make(2)->schema([
                            Select::make('semester_id')
                                ->label('Semester')
                                ->relationship('semester', 'nama')
                                ->searchable()
                                ->preload()
                                ->getOptionLabelFromRecordUsing(fn (Semester $record) => "{$record->tahunAjaran->nama} - {$record->nama}"),

                            TextInput::make('nomor_tagihan')
                                ->label('Nomor Tagihan')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(50)
                                ->default(fn () => 'TGH-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -6))),
                        ]),
                    ]),

                Section::make('Nominal Tagihan')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('nominal')
                                ->label('Nominal')
                                ->required()
                                ->numeric()
                                ->prefix('Rp')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Set $set, $state, $get) {
                                    $nominal = floatval($state ?? 0);
                                    $diskon = floatval($get('diskon') ?? 0);
                                    $set('total_tagihan', $nominal - $diskon);
                                    $set('sisa_tagihan', ($nominal - $diskon) - floatval($get('total_terbayar') ?? 0));
                                }),

                            TextInput::make('diskon')
                                ->label('Diskon')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(0)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Set $set, $state, $get) {
                                    $nominal = floatval($get('nominal') ?? 0);
                                    $diskon = floatval($state ?? 0);
                                    $set('total_tagihan', $nominal - $diskon);
                                    $set('sisa_tagihan', ($nominal - $diskon) - floatval($get('total_terbayar') ?? 0));
                                }),

                            TextInput::make('total_tagihan')
                                ->label('Total Tagihan')
                                ->numeric()
                                ->prefix('Rp')
                                ->disabled()
                                ->dehydrated(),
                        ]),

                        Grid::make(2)->schema([
                            Placeholder::make('total_terbayar_placeholder')
                                ->label('Total Terbayar')
                                ->content(fn ($record) => $record ? 'Rp '.number_format($record->total_terbayar, 0, ',', '.') : 'Rp 0')
                                ->hiddenOn('create'),

                            Placeholder::make('sisa_tagihan_placeholder')
                                ->label('Sisa Tagihan')
                                ->content(fn ($record) => $record ? 'Rp '.number_format($record->sisa_tagihan, 0, ',', '.') : '-')
                                ->hiddenOn('create'),
                        ]),
                    ]),

                Section::make('Tanggal & Status')
                    ->schema([
                        Grid::make(3)->schema([
                            DatePicker::make('tanggal_tagihan')
                                ->label('Tanggal Tagihan')
                                ->required()
                                ->default(now())
                                ->native(false),

                            DatePicker::make('tanggal_jatuh_tempo')
                                ->label('Tanggal Jatuh Tempo')
                                ->native(false),

                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'belum_bayar' => 'Belum Bayar',
                                    'sebagian' => 'Sebagian',
                                    'lunas' => 'Lunas',
                                    'batal' => 'Batal',
                                ])
                                ->default('belum_bayar')
                                ->required(),
                        ]),

                        Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(3),
                    ]),
            ]);
    }
}
