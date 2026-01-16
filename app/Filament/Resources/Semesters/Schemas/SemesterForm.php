<?php

namespace App\Filament\Resources\Semesters\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SemesterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Semester')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('tahun_ajaran_id')
                                ->label('Tahun Ajaran')
                                ->relationship('tahunAjaran', 'nama')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('semester')
                                ->label('Semester')
                                ->options([
                                    1 => 'Ganjil (1)',
                                    2 => 'Genap (2)',
                                ])
                                ->required()
                                ->native(false),
                        ]),
                        TextInput::make('nama')
                            ->label('Nama Semester')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('Semester Ganjil 2025/2026'),
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
                            ->helperText('Hanya satu semester yang bisa aktif. Mengaktifkan ini akan menonaktifkan yang lain.')
                            ->default(false),
                        Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
