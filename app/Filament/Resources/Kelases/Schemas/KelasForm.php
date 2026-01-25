<?php

namespace App\Filament\Resources\Kelases\Schemas;

use App\Models\TahunAjaran;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KelasForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kelas')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('tahun_ajaran_id')
                                ->label('Tahun Ajaran')
                                ->relationship('tahunAjaran', 'nama')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->default(fn () => TahunAjaran::where('is_aktif', true)->first()?->id),
                            TextInput::make('nama')
                                ->label('Nama Kelas')
                                ->required()
                                ->maxLength(20)
                                ->placeholder('7A, 8B, 9C')
                                ->helperText('Contoh: 7A, 8B, X IPA 1'),
                        ]),
                        Grid::make(3)->schema([
                            Select::make('tingkat')
                                ->label('Tingkat')
                                ->required()
                                ->native(false)
                                ->options([
                                    7 => 'Kelas 7',
                                    8 => 'Kelas 8',
                                    9 => 'Kelas 9',
                                    10 => 'Kelas 10',
                                    11 => 'Kelas 11',
                                    12 => 'Kelas 12',
                                ]),
                            TextInput::make('jurusan')
                                ->label('Jurusan')
                                ->maxLength(50)
                                ->placeholder('IPA / IPS / Bahasa')
                                ->helperText('Opsional, untuk SMA/SMK'),
                            TextInput::make('kapasitas')
                                ->label('Kapasitas')
                                ->numeric()
                                ->required()
                                ->default(32)
                                ->minValue(1)
                                ->maxValue(50),
                        ]),
                    ]),

                Section::make('Detail Tambahan')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('wali_kelas_id')
                                ->label('Wali Kelas')
                                ->relationship('waliKelas', 'nama')
                                ->searchable()
                                ->preload()
                                ->placeholder('Pilih wali kelas')
                                ->helperText('Guru yang menjadi wali kelas'),
                            TextInput::make('ruangan')
                                ->label('Ruangan')
                                ->maxLength(50)
                                ->placeholder('Ruang 7A'),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('urutan')
                                ->label('Urutan')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->helperText('Urutan tampil dalam daftar'),
                            Toggle::make('is_active')
                                ->label('Aktif')
                                ->default(true)
                                ->helperText('Kelas yang tidak aktif tidak ditampilkan dalam dropdown'),
                        ]),
                        Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
