<?php

namespace App\Filament\Resources\Prestasis\Schemas;

use App\Models\Semester;
use App\Models\Siswa;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PrestasiForm
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

                Section::make('Data Prestasi')
                    ->schema([
                        TextInput::make('nama_prestasi')
                            ->label('Nama Prestasi')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Juara 1 Olimpiade Matematika'),

                        Grid::make(3)->schema([
                            Select::make('tingkat')
                                ->label('Tingkat')
                                ->options([
                                    'sekolah' => 'Sekolah',
                                    'kecamatan' => 'Kecamatan',
                                    'kabupaten' => 'Kabupaten/Kota',
                                    'provinsi' => 'Provinsi',
                                    'nasional' => 'Nasional',
                                    'internasional' => 'Internasional',
                                ])
                                ->required(),

                            Select::make('jenis')
                                ->label('Jenis')
                                ->options([
                                    'akademik' => 'Akademik',
                                    'non_akademik' => 'Non Akademik',
                                    'olahraga' => 'Olahraga',
                                    'seni' => 'Seni',
                                    'keagamaan' => 'Keagamaan',
                                    'lainnya' => 'Lainnya',
                                ])
                                ->required(),

                            Select::make('peringkat')
                                ->label('Peringkat')
                                ->options([
                                    'juara_1' => 'Juara 1',
                                    'juara_2' => 'Juara 2',
                                    'juara_3' => 'Juara 3',
                                    'harapan_1' => 'Harapan 1',
                                    'harapan_2' => 'Harapan 2',
                                    'harapan_3' => 'Harapan 3',
                                    'peserta' => 'Peserta',
                                    'lainnya' => 'Lainnya',
                                ]),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('penyelenggara')
                                ->label('Penyelenggara')
                                ->maxLength(255),

                            DatePicker::make('tanggal')
                                ->label('Tanggal')
                                ->required()
                                ->native(false),
                        ]),

                        FileUpload::make('bukti')
                            ->label('Bukti/Sertifikat')
                            ->image()
                            ->directory('prestasi-bukti')
                            ->maxSize(2048),

                        Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(3),
                    ]),
            ]);
    }
}
