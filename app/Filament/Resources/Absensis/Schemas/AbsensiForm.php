<?php

namespace App\Filament\Resources\Absensis\Schemas;

use App\Models\Absensi;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\Siswa;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AbsensiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Absensi')
                ->icon('heroicon-o-clipboard-document-check')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('kelas_id')
                            ->label('Kelas')
                            ->options(fn () => Kelas::query()
                                ->whereHas('jadwalPelajarans', fn ($q) => $q->where('is_active', true))
                                ->ordered()
                                ->pluck('nama', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                $set('jadwal_pelajaran_id', null);
                                $set('siswa_id', null);
                            })
                            ->dehydrated(false),
                        Select::make('jadwal_pelajaran_id')
                            ->label('Jadwal Pelajaran')
                            ->options(function ($get) {
                                $kelasId = $get('kelas_id');

                                if (! $kelasId) {
                                    return [];
                                }

                                return JadwalPelajaran::query()
                                    ->where('is_active', true)
                                    ->where('kelas_id', $kelasId)
                                    ->with(['mataPelajaran', 'jamPelajaran'])
                                    ->orderBy('hari')
                                    ->orderBy('jam_pelajaran_id')
                                    ->get()
                                    ->mapWithKeys(fn (JadwalPelajaran $j) => [
                                        $j->id => $j->jadwal_lengkap,
                                    ]);
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        Select::make('siswa_id')
                            ->label('Siswa')
                            ->options(function ($get) {
                                $kelasId = $get('kelas_id');

                                if (! $kelasId) {
                                    return [];
                                }

                                return Siswa::query()
                                    ->where('kelas_id', $kelasId)
                                    ->where('is_active', true)
                                    ->orderBy('nama')
                                    ->get()
                                    ->mapWithKeys(fn (Siswa $s) => [
                                        $s->id => $s->nama_lengkap,
                                    ]);
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),
                    Grid::make(2)->schema([
                        DatePicker::make('tanggal')->label('Tanggal')
                            ->required()
                            ->default(now()),
                        Select::make('status')->label('Status')
                            ->options(Absensi::statusOptions())
                            ->default('hadir')
                            ->required()
                            ->native(false),
                    ]),
                    Textarea::make('keterangan')->label('Keterangan')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
