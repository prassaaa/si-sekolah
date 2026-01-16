<?php

namespace App\Filament\Resources\JabatanPegawais\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JabatanPegawaiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Jabatan')
                    ->schema([
                        TextInput::make('kode')
                            ->label('Kode Jabatan')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20),
                        TextInput::make('nama')
                            ->label('Nama Jabatan')
                            ->required()
                            ->maxLength(255),
                        Select::make('jenis')
                            ->label('Jenis Jabatan')
                            ->options([
                                'Struktural' => 'Struktural',
                                'Fungsional' => 'Fungsional',
                                'Non-Fungsional' => 'Non-Fungsional',
                            ])
                            ->default('Fungsional')
                            ->required(),
                        Select::make('golongan')
                            ->label('Golongan')
                            ->options([
                                'I' => 'Golongan I',
                                'II' => 'Golongan II',
                                'III' => 'Golongan III',
                                'IV' => 'Golongan IV',
                                'Non-PNS' => 'Non-PNS',
                            ])
                            ->searchable(),
                    ])
                    ->columns(2),

                Section::make('Kompensasi')
                    ->schema([
                        TextInput::make('gaji_pokok')
                            ->label('Gaji Pokok')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->minValue(0),
                        TextInput::make('tunjangan')
                            ->label('Tunjangan')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->minValue(0),
                    ])
                    ->columns(2),

                Section::make('Lainnya')
                    ->schema([
                        Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('urutan')
                            ->label('Urutan')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
