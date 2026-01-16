<?php

namespace App\Filament\Resources\Pajaks\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PajakForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->label('Nama Pajak')
                    ->required()
                    ->maxLength(255),
                TextInput::make('persentase')
                    ->label('Persentase (%)')
                    ->required()
                    ->numeric()
                    ->suffix('%')
                    ->minValue(0)
                    ->maxValue(100),
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
