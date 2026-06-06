<?php

namespace App\Filament\Resources\Ruangans\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RuanganForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Ruangan')
                    ->schema([
                        TextInput::make('kode')
                            ->label('Kode')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->helperText('Contoh: R-101, LAB-1'),
                        TextInput::make('nama')
                            ->label('Nama Ruangan')
                            ->required()
                            ->maxLength(255),
                        Select::make('jenis')
                            ->label('Jenis')
                            ->required()
                            ->options([
                                'kelas' => 'Kelas',
                                'lab' => 'Laboratorium',
                                'kantor' => 'Kantor',
                                'gudang' => 'Gudang',
                                'perpustakaan' => 'Perpustakaan',
                                'aula' => 'Aula',
                                'lainnya' => 'Lainnya',
                            ]),
                        Select::make('penanggung_jawab_id')
                            ->label('Penanggung Jawab')
                            ->relationship('penanggungJawab', 'nama')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])
                    ->columns(2),

                Section::make('Detail Lokasi')
                    ->schema([
                        TextInput::make('gedung')
                            ->label('Gedung')
                            ->maxLength(100)
                            ->placeholder('Contoh: Gedung A'),
                        TextInput::make('lantai')
                            ->label('Lantai')
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('kapasitas')
                            ->label('Kapasitas')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('orang'),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                        Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
