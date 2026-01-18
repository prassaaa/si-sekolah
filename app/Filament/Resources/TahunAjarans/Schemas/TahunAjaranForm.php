<?php

namespace App\Filament\Resources\TahunAjarans\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TahunAjaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Tahun Ajaran')
                    ->icon('heroicon-o-calendar-date-range')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('kode')
                                ->label('Kode Tahun Ajaran')
                                ->required()
                                ->maxLength(9)
                                ->placeholder('2025/2026')
                                ->unique(ignoreRecord: true)
                                ->helperText('Format: YYYY/YYYY'),
                            TextInput::make('nama')
                                ->label('Nama Tahun Ajaran')
                                ->required()
                                ->maxLength(50)
                                ->placeholder('Tahun Ajaran 2025/2026'),
                        ]),
                        Grid::make(2)->schema([
                            DatePicker::make('tanggal_mulai')
                                ->label('Tanggal Mulai')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y'),
                            DatePicker::make('tanggal_selesai')
                                ->label('Tanggal Selesai')
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->afterOrEqual('tanggal_mulai'),
                        ]),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->helperText('Hanya satu tahun ajaran yang bisa aktif. Mengaktifkan ini akan menonaktifkan yang lain.')
                            ->default(false),
                        Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
