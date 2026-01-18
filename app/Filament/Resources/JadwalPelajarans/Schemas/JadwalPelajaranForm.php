<?php

namespace App\Filament\Resources\JadwalPelajarans\Schemas;

use App\Models\JadwalPelajaran;
use App\Models\Semester;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JadwalPelajaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Jadwal Pelajaran')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('semester_id')
                                ->label('Semester')
                                ->relationship('semester', 'nama')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->default(fn () => Semester::where('is_active', true)->first()?->id),
                            Select::make('kelas_id')
                                ->label('Kelas')
                                ->relationship('kelas', 'nama')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ]),
                        Grid::make(2)->schema([
                            Select::make('hari')
                                ->label('Hari')
                                ->required()
                                ->native(false)
                                ->options(JadwalPelajaran::hariOptions()),
                            Select::make('jam_pelajaran_id')
                                ->label('Jam Pelajaran')
                                ->relationship('jamPelajaran', 'nama')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ]),
                        Grid::make(2)->schema([
                            Select::make('mata_pelajaran_id')
                                ->label('Mata Pelajaran')
                                ->relationship('mataPelajaran', 'nama')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('guru_id')
                                ->label('Guru')
                                ->relationship('guru', 'nama')
                                ->searchable()
                                ->preload()
                                ->placeholder('Pilih guru'),
                        ]),
                    ]),

                Section::make('Pengaturan')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                        Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
