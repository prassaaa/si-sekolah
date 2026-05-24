<?php

namespace App\Filament\Resources\RfidDevices\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RfidDeviceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Device')->schema([
                Grid::make(2)->schema([
                    TextInput::make('nama')
                        ->label('Nama Device')
                        ->required()
                        ->maxLength(100)
                        ->placeholder('Reader Gerbang Utama (Masuk)'),

                    TextInput::make('kode')
                        ->label('Kode Unik')
                        ->required()
                        ->maxLength(50)
                        ->unique(ignoreRecord: true)
                        ->alphaDash()
                        ->placeholder('GERBANG-IN-01')
                        ->helperText('Identifier alphanumeric + dash, harus unik'),
                ]),

                Grid::make(2)->schema([
                    Select::make('jenis')
                        ->label('Jenis')
                        ->options([
                            'gerbang_masuk' => 'Gerbang Masuk',
                            'gerbang_pulang' => 'Gerbang Pulang',
                            'serbaguna' => 'Serbaguna',
                        ])
                        ->required()
                        ->default('serbaguna'),

                    TextInput::make('lokasi')
                        ->label('Lokasi Fisik')
                        ->maxLength(150)
                        ->placeholder('Gerbang Utama Depan'),
                ]),

                Toggle::make('is_active')
                    ->label('Device Aktif')
                    ->default(true)
                    ->helperText('Device nonaktif tidak bisa kirim scan ke API'),

                Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->rows(3),
            ]),
        ]);
    }
}
