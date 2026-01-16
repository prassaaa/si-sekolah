<?php

namespace App\Filament\Resources\Kelulusans\Schemas;

use App\Models\Pegawai;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class KelulusanForm
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

                            Select::make('tahun_ajaran_id')
                                ->label('Tahun Ajaran')
                                ->relationship('tahunAjaran', 'nama')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->getOptionLabelFromRecordUsing(fn (TahunAjaran $record) => $record->nama),
                        ]),
                    ]),

                Section::make('Data Kelulusan')
                    ->schema([
                        Grid::make(3)->schema([
                            DatePicker::make('tanggal_lulus')
                                ->label('Tanggal Lulus')
                                ->required()
                                ->native(false),

                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'lulus' => 'Lulus',
                                    'tidak_lulus' => 'Tidak Lulus',
                                    'pending' => 'Pending',
                                ])
                                ->default('pending')
                                ->required()
                                ->live(),

                            Select::make('predikat')
                                ->label('Predikat')
                                ->options([
                                    'sangat_baik' => 'Sangat Baik',
                                    'baik' => 'Baik',
                                    'cukup' => 'Cukup',
                                    'kurang' => 'Kurang',
                                ]),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('nomor_ijazah')
                                ->label('Nomor Ijazah')
                                ->maxLength(50),

                            TextInput::make('nomor_skhun')
                                ->label('Nomor SKHUN')
                                ->maxLength(50),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('nilai_akhir')
                                ->label('Nilai Akhir')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->step(0.01),

                            TextInput::make('tujuan_sekolah')
                                ->label('Tujuan Sekolah')
                                ->maxLength(255)
                                ->placeholder('Nama sekolah lanjutan'),
                        ]),
                    ]),

                Section::make('Keputusan')
                    ->schema([
                        Select::make('disetujui_oleh')
                            ->label('Disetujui Oleh')
                            ->options(fn () => Pegawai::active()->pluck('nama', 'id'))
                            ->searchable()
                            ->preload(),

                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->rows(3),
                    ]),
            ]);
    }
}
