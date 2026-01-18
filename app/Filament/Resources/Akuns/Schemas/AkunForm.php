<?php

namespace App\Filament\Resources\Akuns\Schemas;

use App\Models\Akun;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AkunForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Akun')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('kode')
                                ->label('Kode Akun')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(20)
                                ->placeholder('Contoh: 1-1001'),

                            TextInput::make('nama')
                                ->label('Nama Akun')
                                ->required()
                                ->maxLength(100),
                        ]),

                        Grid::make(2)->schema([
                            Select::make('tipe')
                                ->label('Tipe Akun')
                                ->options([
                                    'aset' => 'Aset',
                                    'liabilitas' => 'Liabilitas',
                                    'ekuitas' => 'Ekuitas',
                                    'pendapatan' => 'Pendapatan',
                                    'beban' => 'Beban',
                                ])
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($set, $state) {
                                    $posisiNormal = in_array($state, ['aset', 'beban']) ? 'debit' : 'kredit';
                                    $set('posisi_normal', $posisiNormal);
                                }),

                            Select::make('kategori')
                                ->label('Kategori')
                                ->options([
                                    'lancar' => 'Lancar',
                                    'tetap' => 'Tetap',
                                    'jangka_panjang' => 'Jangka Panjang',
                                    'operasional' => 'Operasional',
                                    'non_operasional' => 'Non Operasional',
                                ]),
                        ]),

                        Grid::make(2)->schema([
                            Select::make('parent_id')
                                ->label('Parent Akun')
                                ->relationship('parent', 'nama')
                                ->searchable()
                                ->preload()
                                ->getOptionLabelFromRecordUsing(fn (Akun $record) => "{$record->kode} - {$record->nama}"),

                            Select::make('posisi_normal')
                                ->label('Posisi Normal')
                                ->options([
                                    'debit' => 'Debit',
                                    'kredit' => 'Kredit',
                                ])
                                ->required()
                                ->default('debit'),
                        ]),
                    ]),

                Section::make('Saldo')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('saldo_awal')
                                ->label('Saldo Awal')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(0),

                            TextInput::make('saldo_akhir')
                                ->label('Saldo Akhir')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(0)
                                ->disabled()
                                ->dehydrated(),

                            TextInput::make('level')
                                ->label('Level')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->maxValue(5),
                        ]),

                        Grid::make(2)->schema([
                            Toggle::make('is_active')
                                ->label('Aktif')
                                ->default(true)
                                ->inline(false),

                            Textarea::make('deskripsi')
                                ->label('Deskripsi')
                                ->rows(2),
                        ]),
                    ]),
            ]);
    }
}
