<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanSiswaStats;
use App\Models\Kelas;
use App\Models\Siswa;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class LaporanSiswa extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Laporan Siswa';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'laporan/siswa';

    protected string $view = 'filament.pages.laporan-siswa';

    public ?int $kelas_id = null;

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Siswa';
    }

    public function mount(): void
    {
        $this->filter();
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('kelas_id')
                    ->label('Kelas')
                    ->options(
                        Kelas::query()
                            ->where('is_active', true)
                            ->orderBy('tingkat')
                            ->orderBy('nama')
                            ->pluck('nama', 'id'),
                    )
                    ->placeholder('Semua Kelas')
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
            ])
            ->columns(1);
    }

    public function filter(): void
    {
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
            ->when(
                $this->kelas_id,
                fn ($q) => $q->where('kelas_id', $this->kelas_id),
            )
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $siswaPerJenisKelamin = Siswa::query()
            ->when(
                $this->kelas_id,
                fn ($q) => $q->where('kelas_id', $this->kelas_id),
            )
            ->selectRaw('jenis_kelamin, COUNT(*) as count')
            ->groupBy('jenis_kelamin')
            ->pluck('count', 'jenis_kelamin')
            ->toArray();

        $siswaPerKelas = Siswa::query()
            ->join('kelas', 'siswas.kelas_id', '=', 'kelas.id')
            ->when(
                $this->kelas_id,
                fn ($q) => $q->where('kelas_id', $this->kelas_id),
            )
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

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanSiswaStats::make([
                'summary' => $this->summary,
            ]),
        ];
    }
}
