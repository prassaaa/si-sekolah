<?php

namespace App\Filament\Resources\Konselings\Schemas;

use App\Models\Pegawai;
use App\Models\Semester;
use App\Models\Siswa;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KonselingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Siswa & Konselor')
                    ->schema([
                        Grid::make(3)->schema([
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

                            Select::make('konselor_id')
                                ->label('Konselor')
                                ->options(fn () => Pegawai::active()->pluck('nama', 'id'))
                                ->searchable()
                                ->preload()
                                ->required(),
                        ]),
                    ]),

                Section::make('Jadwal Konseling')
                    ->schema([
                        Grid::make(3)->schema([
                            DatePicker::make('tanggal')
                                ->label('Tanggal')
                                ->required()
                                ->native(false)
                                ->default(now()),

                            TimePicker::make('waktu_mulai')
                                ->label('Waktu Mulai')
                                ->required()
                                ->seconds(false),

                            TimePicker::make('waktu_selesai')
                                ->label('Waktu Selesai')
                                ->seconds(false)
                                ->after('waktu_mulai'),
                        ]),
                    ]),

                Section::make('Detail Konseling')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('jenis')
                                ->label('Jenis Konseling')
                                ->options([
                                    'individu' => 'Individu',
                                    'kelompok' => 'Kelompok',
                                    'keluarga' => 'Keluarga',
                                ])
                                ->required()
                                ->default('individu'),

                            Select::make('kategori')
                                ->label('Kategori')
                                ->options([
                                    'akademik' => 'Akademik',
                                    'pribadi' => 'Pribadi',
                                    'sosial' => 'Sosial',
                                    'karir' => 'Karir',
                                    'lainnya' => 'Lainnya',
                                ])
                                ->required()
                                ->default('pribadi'),

                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'dijadwalkan' => 'Dijadwalkan',
                                    'berlangsung' => 'Sedang Berlangsung',
                                    'selesai' => 'Selesai',
                                    'batal' => 'Dibatalkan',
                                ])
                                ->default('dijadwalkan')
                                ->required(),
                        ]),

                        Textarea::make('permasalahan')
                            ->label('Permasalahan')
                            ->required()
                            ->rows(3)
                            ->placeholder('Deskripsi permasalahan yang dikonseling...'),

                        Textarea::make('hasil_konseling')
                            ->label('Hasil Konseling')
                            ->rows(3)
                            ->placeholder('Hasil dari sesi konseling...'),

                        Textarea::make('rekomendasi')
                            ->label('Rekomendasi')
                            ->rows(3)
                            ->placeholder('Rekomendasi tindak lanjut...'),
                    ]),

                Section::make('Tindak Lanjut')
                    ->schema([
                        Grid::make(2)->schema([
                            Checkbox::make('perlu_tindak_lanjut')
                                ->label('Perlu Tindak Lanjut'),

                            DatePicker::make('tanggal_tindak_lanjut')
                                ->label('Tanggal Tindak Lanjut')
                                ->native(false),
                        ]),

                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->rows(2),
                    ]),
            ]);
    }
}
