<?php

namespace App\Filament\Resources\Tahfidzs\Schemas;

use App\Models\Pegawai;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\Tahfidz;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Schemas\Schema;

class TahfidzForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Siswa & Semester')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('siswa_id')
                                ->label('Siswa')
                                ->relationship('siswa', 'nama')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->getOptionLabelFromRecordUsing(fn (Siswa $record) => "{$record->nisn} - {$record->nama}"),

                            Select::make('semester_id')
                                ->label('Semester')
                                ->relationship('semester', 'nama')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->getOptionLabelFromRecordUsing(fn (Semester $record) => "{$record->tahunAjaran?->nama} - {$record->nama}"),
                        ]),

                        Select::make('penguji_id')
                            ->label('Penguji')
                            ->options(
                                Pegawai::guru()
                                    ->active()
                                    ->orderBy('nama')
                                    ->pluck('nama', 'id')
                            )
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(1),

                Section::make('Data Hafalan')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('surah')
                                ->label('Surah')
                                ->options(Tahfidz::surahOptions())
                                ->searchable()
                                ->required(),

                            TextInput::make('juz')
                                ->label('Juz')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(30),
                        ]),

                        Grid::make(3)->schema([
                            TextInput::make('ayat_mulai')
                                ->label('Ayat Mulai')
                                ->numeric()
                                ->minValue(1)
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => self::calculateJumlahAyat($get, $set)),

                            TextInput::make('ayat_selesai')
                                ->label('Ayat Selesai')
                                ->numeric()
                                ->minValue(1)
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => self::calculateJumlahAyat($get, $set)),

                            TextInput::make('jumlah_ayat')
                                ->label('Jumlah Ayat')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(),
                        ]),
                    ]),

                Section::make('Penilaian')
                    ->schema([
                        Grid::make(3)->schema([
                            DatePicker::make('tanggal')
                                ->label('Tanggal')
                                ->required()
                                ->default(now())
                                ->native(false),

                            Select::make('jenis')
                                ->label('Jenis')
                                ->options([
                                    'setoran' => 'Setoran',
                                    'murojaah' => 'Murojaah',
                                    'ujian' => 'Ujian',
                                ])
                                ->required()
                                ->default('setoran'),

                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'lulus' => 'Lulus',
                                    'mengulang' => 'Mengulang',
                                ])
                                ->required()
                                ->default('pending'),
                        ]),

                        TextInput::make('nilai')
                            ->label('Nilai')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('/100'),

                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function calculateJumlahAyat(Get $get, Set $set): void
    {
        $ayatMulai = (int) $get('ayat_mulai');
        $ayatSelesai = (int) $get('ayat_selesai');

        if ($ayatMulai > 0 && $ayatSelesai > 0 && $ayatSelesai >= $ayatMulai) {
            $set('jumlah_ayat', $ayatSelesai - $ayatMulai + 1);
        }
    }
}
