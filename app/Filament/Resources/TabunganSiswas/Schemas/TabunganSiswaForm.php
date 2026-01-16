<?php

namespace App\Filament\Resources\TabunganSiswas\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TabunganSiswaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('siswa_id')
                    ->relationship('siswa', 'nama')
                    ->label('Siswa')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('jenis')
                    ->options([
                        'setor' => 'Setor',
                        'tarik' => 'Tarik',
                    ])
                    ->default('setor')
                    ->required()
                    ->native(false),
                TextInput::make('nominal')
                    ->label('Nominal')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->minValue(1),
                DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->required()
                    ->default(now()),
                TextInput::make('keterangan')
                    ->label('Keterangan')
                    ->maxLength(255),
            ]);
    }
}
