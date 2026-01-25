<?php

namespace App\Filament\Resources\JurnalUmums\Schemas;

use App\Models\Akun;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class JurnalUmumForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Jurnal')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('nomor_bukti')
                                ->label('Nomor Bukti')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(50)
                                ->default(fn () => 'JU-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -6))),

                            DatePicker::make('tanggal')
                                ->label('Tanggal')
                                ->required()
                                ->default(now())
                                ->native(false),
                        ]),

                        Select::make('akun_id')
                            ->label('Akun')
                            ->relationship('akun', 'nama', fn ($query) => $query->active())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->getOptionLabelFromRecordUsing(fn (Akun $record) => "{$record->kode} - {$record->nama}"),

                        Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->required()
                            ->rows(3),
                    ]),

                Section::make('Nominal')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('debit')
                                ->label('Debit')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(0)
                                ->minValue(0)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    if ($state > 0) {
                                        $set('kredit', 0);
                                    }
                                }),

                            TextInput::make('kredit')
                                ->label('Kredit')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(0)
                                ->minValue(0)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    if ($state > 0) {
                                        $set('debit', 0);
                                    }
                                }),
                        ]),
                    ]),

                Section::make('Referensi')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('jenis_referensi')
                                ->label('Jenis Referensi')
                                ->options([
                                    'pembayaran' => 'Pembayaran',
                                    'penerimaan' => 'Penerimaan',
                                    'penyesuaian' => 'Penyesuaian',
                                    'koreksi' => 'Koreksi',
                                    'lainnya' => 'Lainnya',
                                ]),

                            TextInput::make('referensi')
                                ->label('No. Referensi')
                                ->maxLength(100),
                        ]),

                        Select::make('created_by')
                            ->label('Dibuat Oleh')
                            ->relationship('creator', 'name')
                            ->default(fn () => Auth::id())
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->collapsed(),
            ]);
    }
}
