<?php

namespace App\Filament\Resources\KasKeluars\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class KasKeluarForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nomor_bukti')
                    ->label('Nomor Bukti')
                    ->disabled()
                    ->dehydrated()
                    ->placeholder('Auto-generate'),
                Select::make('akun_id')
                    ->relationship('akun', 'nama')
                    ->label('Akun Kas/Bank')
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->required()
                    ->default(now()),
                TextInput::make('nominal')
                    ->label('Nominal')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->minValue(1),
                TextInput::make('penerima')
                    ->label('Penerima')
                    ->maxLength(255),
                Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
