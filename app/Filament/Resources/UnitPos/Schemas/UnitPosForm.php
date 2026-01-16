<?php

namespace App\Filament\Resources\UnitPos\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UnitPosForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('kode')
                    ->label('Kode Unit')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50),
                TextInput::make('nama')
                    ->label('Nama Unit')
                    ->required()
                    ->maxLength(255),
                TextInput::make('alamat')
                    ->label('Alamat')
                    ->maxLength(255),
                TextInput::make('telepon')
                    ->label('Telepon')
                    ->tel()
                    ->maxLength(20),
                Select::make('akun_id')
                    ->relationship('akun', 'nama')
                    ->label('Akun Kas/Bank')
                    ->searchable()
                    ->preload(),
                Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->rows(3)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }
}
