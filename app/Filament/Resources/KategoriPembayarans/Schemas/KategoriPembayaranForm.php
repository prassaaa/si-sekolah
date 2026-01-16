<?php

namespace App\Filament\Resources\KategoriPembayarans\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class KategoriPembayaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Kategori')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('kode')
                                ->label('Kode')
                                ->required()
                                ->maxLength(20)
                                ->unique(ignoreRecord: true)
                                ->placeholder('SPP'),

                            TextInput::make('nama')
                                ->label('Nama Kategori')
                                ->required()
                                ->maxLength(100)
                                ->placeholder('Sumbangan Pembinaan Pendidikan'),
                        ]),

                        Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->rows(3),

                        Grid::make(2)->schema([
                            TextInput::make('urutan')
                                ->label('Urutan')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),

                            Toggle::make('is_active')
                                ->label('Aktif')
                                ->default(true)
                                ->inline(false),
                        ]),
                    ]),
            ]);
    }
}
