<?php

namespace App\Filament\Resources\SettingGajis\Schemas;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SettingGajiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pegawai')
                    ->schema([
                        Select::make('pegawai_id')
                            ->label('Pegawai')
                            ->relationship('pegawai', 'nama')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])->columns(2),

                Section::make('Gaji Pokok')
                    ->schema([
                        TextInput::make('gaji_pokok')
                            ->label('Gaji Pokok')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                    ]),

                Section::make('Tunjangan')
                    ->schema([
                        TextInput::make('tunjangan_jabatan')
                            ->label('Tunjangan Jabatan')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                        TextInput::make('tunjangan_kehadiran')
                            ->label('Tunjangan Kehadiran')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                        TextInput::make('tunjangan_transport')
                            ->label('Tunjangan Transport')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                        TextInput::make('tunjangan_makan')
                            ->label('Tunjangan Makan')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                        TextInput::make('tunjangan_lainnya')
                            ->label('Tunjangan Lainnya')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                    ])->columns(3),

                Section::make('Potongan')
                    ->schema([
                        TextInput::make('potongan_bpjs')
                            ->label('Potongan BPJS')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                        TextInput::make('potongan_pph21')
                            ->label('Potongan PPh21')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                        TextInput::make('potongan_lainnya')
                            ->label('Potongan Lainnya')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0),
                    ])->columns(3),
            ]);
    }
}
