<?php

namespace App\Filament\Pages;

use App\Models\Kelas;
use App\Models\Siswa;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class LaporanSiswa extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'Laporan Siswa';

    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.laporan-siswa';

    public ?array $data = [];

    public ?int $kelas_id = null;

    public array $summary = [];

    public function mount(): void
    {
        $this->loadReport();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Filter')
                    ->schema([
                        Select::make('kelas_id')
                            ->label('Kelas')
                            ->options(Kelas::pluck('nama', 'id'))
                            ->placeholder('Semua Kelas')
                            ->live()
                            ->afterStateUpdated(fn () => $this->filter()),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function filter(): void
    {
        $this->kelas_id = $this->data['kelas_id'] ?? null;
        $this->loadReport();
    }

    protected function loadReport(): void
    {
        $query = Siswa::query();

        if ($this->kelas_id) {
            $query->where('kelas_id', $this->kelas_id);
        }

        $totalSiswa = $query->count();

        $siswaPerStatus = Siswa::query()
            ->when($this->kelas_id, fn ($q) => $q->where('kelas_id', $this->kelas_id))
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $siswaPerJenisKelamin = Siswa::query()
            ->when($this->kelas_id, fn ($q) => $q->where('kelas_id', $this->kelas_id))
            ->selectRaw('jenis_kelamin, COUNT(*) as count')
            ->groupBy('jenis_kelamin')
            ->pluck('count', 'jenis_kelamin')
            ->toArray();

        $siswaPerKelas = Siswa::query()
            ->join('kelas', 'siswas.kelas_id', '=', 'kelas.id')
            ->when($this->kelas_id, fn ($q) => $q->where('kelas_id', $this->kelas_id))
            ->selectRaw('kelas.nama as kelas_nama, COUNT(siswas.id) as count')
            ->groupBy('kelas.id', 'kelas.nama')
            ->pluck('count', 'kelas_nama')
            ->toArray();

        $this->summary = [
            'total_siswa' => $totalSiswa,
            'siswa_per_status' => $siswaPerStatus,
            'siswa_per_jenis_kelamin' => $siswaPerJenisKelamin,
            'siswa_per_kelas' => $siswaPerKelas,
        ];
    }
}
