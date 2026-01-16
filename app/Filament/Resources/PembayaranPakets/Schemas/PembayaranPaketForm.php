<?php

namespace App\Filament\Resources\PembayaranPakets\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PembayaranPaketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->label('Nama Paket')
                    ->required()
                    ->maxLength(255),
                Select::make('tahun_ajaran_id')
                    ->relationship('tahunAjaran', 'nama')
                    ->label('Tahun Ajaran')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('total_biaya')
                    ->label('Total Biaya')
                    ->numeric()
                    ->prefix('Rp')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Total dihitung dari item paket'),
                Textarea::make('deskripsi')
                    ->label('Deskripsi')
                    ->rows(3)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }
}
