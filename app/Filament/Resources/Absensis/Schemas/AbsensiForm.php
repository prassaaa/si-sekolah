<?php

namespace App\Filament\Resources\Absensis\Schemas;

use App\Models\Absensi;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\Siswa;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
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
                            ->exists('jadwal_pelajarans', 'id')
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
                            ->required()
                            ->live()
                            ->exists('siswas', 'id'),
                    ]),
                    Grid::make(2)->schema([
                        DatePicker::make('tanggal')->label('Tanggal')
                            ->required()
                            ->default(now())
                            ->live(),
                        Select::make('status')->label('Status')
                            ->options(Absensi::statusOptions())
                            ->default('hadir')
                            ->required()
                            ->native(false),
                    ]),
                    Placeholder::make('status_absensi_existing')
                        ->label('Status Data Absensi')
                        ->hiddenOn('edit')
                        ->content(function ($get): string {
                            $jadwalId = $get('jadwal_pelajaran_id');
                            $siswaId = $get('siswa_id');
                            $tanggal = $get('tanggal');

                            if (! $jadwalId || ! $siswaId || ! $tanggal) {
                                return 'Pilih jadwal, siswa, dan tanggal untuk cek data absensi.';
                            }

                            $existing = Absensi::query()
                                ->where('jadwal_pelajaran_id', $jadwalId)
                                ->where('siswa_id', $siswaId)
                                ->whereDate('tanggal', (string) $tanggal)
                                ->first();

                            if (! $existing) {
                                return 'Belum diabsen untuk kombinasi ini.';
                            }

                            $statusLabel = Absensi::statusOptions()[$existing->status] ?? $existing->status;

                            return "Sudah diabsen ({$statusLabel}).";
                        })
                        ->columnSpanFull(),
                    Textarea::make('keterangan')->label('Keterangan')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
