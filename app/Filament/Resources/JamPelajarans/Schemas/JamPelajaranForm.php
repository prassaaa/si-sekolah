<?php

namespace App\Filament\Resources\JamPelajarans\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JamPelajaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Jam Pelajaran')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('jam_ke')
                                ->label('Jam Ke')
                                ->required()
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(15),
                            Select::make('jenis')
                                ->label('Jenis')
                                ->options([
                                    'Reguler' => 'Reguler',
                                    'Istirahat' => 'Istirahat',
                                    'Upacara' => 'Upacara',
                                    'Ekstrakurikuler' => 'Ekstrakurikuler',
                                ])
                                ->required()
                                ->native(false)
                                ->default('Reguler'),
                        ]),
                        Grid::make(3)->schema([
                            TimePicker::make('waktu_mulai')
                                ->label('Waktu Mulai')
                                ->required()
                                ->seconds(false)
                                ->native(false),
                            TimePicker::make('waktu_selesai')
                                ->label('Waktu Selesai')
                                ->required()
                                ->seconds(false)
                                ->native(false)
                                ->after('waktu_mulai'),
                            TextInput::make('durasi')
                                ->label('Durasi (menit)')
                                ->required()
                                ->numeric()
                                ->default(45)
                                ->suffix('menit'),
                        ]),
                        TextInput::make('keterangan')
                            ->label('Keterangan')
                            ->maxLength(100)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),
            ]);
    }
}
