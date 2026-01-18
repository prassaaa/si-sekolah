<?php

namespace App\Filament\Resources\Pelanggarans\Schemas;

use App\Models\Pegawai;
use App\Models\Semester;
use App\Models\Siswa;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PelanggaranForm
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

                Section::make('Data Pelanggaran')
                    ->schema([
                        Grid::make(2)->schema([
                            DatePicker::make('tanggal')
                                ->label('Tanggal Kejadian')
                                ->required()
                                ->native(false)
                                ->default(now()),

                            Select::make('pelapor_id')
                                ->label('Pelapor')
                                ->options(fn () => Pegawai::active()->pluck('nama', 'id'))
                                ->searchable()
                                ->preload(),
                        ]),

                        TextInput::make('jenis_pelanggaran')
                            ->label('Jenis Pelanggaran')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Terlambat masuk sekolah'),

                        Grid::make(2)->schema([
                            Select::make('kategori')
                                ->label('Kategori')
                                ->options([
                                    'ringan' => 'Ringan',
                                    'sedang' => 'Sedang',
                                    'berat' => 'Berat',
                                ])
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, $set) {
                                    $poin = match ($state) {
                                        'ringan' => 5,
                                        'sedang' => 15,
                                        'berat' => 30,
                                        default => 0,
                                    };
                                    $set('poin', $poin);
                                }),

                            TextInput::make('poin')
                                ->label('Poin Pelanggaran')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->default(5),
                        ]),

                        Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->placeholder('Kronologi kejadian...'),

                        FileUpload::make('bukti')
                            ->label('Bukti')
                            ->image()
                            ->directory('pelanggaran-bukti')
                            ->maxSize(2048),
                    ]),

                Section::make('Tindak Lanjut')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'proses' => 'Dalam Proses',
                                    'selesai' => 'Selesai',
                                    'batal' => 'Dibatalkan',
                                ])
                                ->default('proses')
                                ->required(),
                        ]),

                        Textarea::make('tindak_lanjut')
                            ->label('Tindak Lanjut')
                            ->rows(3)
                            ->placeholder('Tindakan yang diberikan...'),

                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->rows(2),
                    ]),
            ]);
    }
}
