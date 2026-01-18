<?php

namespace App\Filament\Resources\MataPelajarans\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MataPelajaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Mata Pelajaran')
                    ->icon('heroicon-o-book-open')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('kode')
                                ->label('Kode')
                                ->required()
                                ->maxLength(10)
                                ->unique(ignoreRecord: true)
                                ->placeholder('MTK'),
                            TextInput::make('nama')
                                ->label('Nama Mata Pelajaran')
                                ->required()
                                ->maxLength(100)
                                ->placeholder('Matematika'),
                            TextInput::make('singkatan')
                                ->label('Singkatan')
                                ->maxLength(10)
                                ->placeholder('MTK'),
                        ]),
                        Grid::make(3)->schema([
                            Select::make('kelompok')
                                ->label('Kelompok')
                                ->options([
                                    'Kelompok A' => 'Kelompok A (Umum)',
                                    'Kelompok B' => 'Kelompok B',
                                    'Kelompok C' => 'Kelompok C (Peminatan)',
                                    'Muatan Lokal' => 'Muatan Lokal',
                                ])
                                ->native(false)
                                ->searchable(),
                            Select::make('jenjang')
                                ->label('Jenjang')
                                ->options([
                                    'TK' => 'TK',
                                    'SD' => 'SD',
                                    'SMP' => 'SMP',
                                    'SMA' => 'SMA',
                                    'SMK' => 'SMK',
                                ])
                                ->native(false),
                            TextInput::make('urutan')
                                ->label('Urutan')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('jam_per_minggu')
                                ->label('Jam/Minggu')
                                ->numeric()
                                ->required()
                                ->default(2)
                                ->minValue(1)
                                ->maxValue(10)
                                ->suffix('jam'),
                            TextInput::make('kkm')
                                ->label('KKM')
                                ->numeric()
                                ->required()
                                ->default(75)
                                ->minValue(0)
                                ->maxValue(100)
                                ->helperText('Kriteria Ketuntasan Minimal'),
                        ]),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                        Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
