<?php

namespace App\Filament\Resources\SarprasKategoris\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SarprasKategoriForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kategori')
                    ->schema([
                        TextInput::make('kode')
                            ->label('Kode')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->helperText('Contoh: ELK, MBL, LAB'),
                        TextInput::make('nama')
                            ->label('Nama Kategori')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
