<?php

namespace App\Filament\Resources\KenaikanKelass\Schemas;

use App\Models\Kelas;
use App\Models\Pegawai;
use App\Models\Semester;
use App\Models\Siswa;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KenaikanKelasForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Siswa')
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
                    ]),

                Section::make('Data Kelas')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('kelas_asal_id')
                                ->label('Kelas Asal')
                                ->options(fn () => Kelas::with('tahunAjaran')->get()->mapWithKeys(fn ($k) => [$k->id => $k->tahunAjaran?->nama.' - '.$k->nama]))
                                ->searchable()
                                ->preload()
                                ->required(),

                            Select::make('kelas_tujuan_id')
                                ->label('Kelas Tujuan')
                                ->options(fn () => Kelas::with('tahunAjaran')->get()->mapWithKeys(fn ($k) => [$k->id => $k->tahunAjaran?->nama.' - '.$k->nama]))
                                ->searchable()
                                ->preload()
                                ->helperText('Kosongkan jika siswa tinggal kelas atau mutasi'),
                        ]),
                    ]),

                Section::make('Status & Nilai')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'naik' => 'Naik Kelas',
                                    'tinggal' => 'Tinggal Kelas',
                                    'mutasi_keluar' => 'Mutasi Keluar',
                                    'pending' => 'Pending',
                                ])
                                ->default('pending')
                                ->required(),

                            TextInput::make('nilai_rata_rata')
                                ->label('Nilai Rata-rata')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->step(0.01),

                            TextInput::make('peringkat')
                                ->label('Peringkat')
                                ->numeric()
                                ->minValue(1),
                        ]),
                    ]),

                Section::make('Keputusan')
                    ->schema([
                        Grid::make(2)->schema([
                            DatePicker::make('tanggal_keputusan')
                                ->label('Tanggal Keputusan')
                                ->native(false),

                            Select::make('disetujui_oleh')
                                ->label('Disetujui Oleh')
                                ->options(fn () => Pegawai::active()->pluck('nama', 'id'))
                                ->searchable()
                                ->preload(),
                        ]),

                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->rows(3),
                    ]),
            ]);
    }
}
