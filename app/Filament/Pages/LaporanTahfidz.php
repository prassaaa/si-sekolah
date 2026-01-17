<?php

namespace App\Filament\Pages;

use App\Models\Kelas;
use App\Models\Semester;
use App\Models\Tahfidz;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class LaporanTahfidz extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Laporan Tahfidz';

    protected static ?string $slug = 'laporan/tahfidz';

    protected string $view = 'filament.pages.laporan-tahfidz';

    public ?int $semester_id = null;

    public ?int $kelas_id = null;

    public Collection $data;

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Rekap Tahfidz';
    }

    public function mount(): void
    {
        $this->semester_id = Semester::query()
            ->where('is_active', true)
            ->value('id');

        $this->data = collect();
        $this->filter();
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('semester_id')
                    ->label('Semester')
                    ->options(Semester::query()->orderByDesc('tahun_ajaran_id')->pluck('nama', 'id'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
                Select::make('kelas_id')
                    ->label('Kelas')
                    ->options(Kelas::query()->where('is_active', true)->orderBy('tingkat')->orderBy('nama')->pluck('nama', 'id'))
                    ->placeholder('Semua Kelas')
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
            ])
            ->columns(2);
    }

    public function filter(): void
    {
        if (! $this->semester_id) {
            $this->data = collect();
            $this->summary = [];

            return;
        }

        $query = Tahfidz::query()
            ->with(['siswa.kelas', 'penguji'])
            ->where('semester_id', $this->semester_id);

        if ($this->kelas_id) {
            $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $this->kelas_id));
        }

        $tahfidzs = $query->get();

        // Group by siswa and calculate totals
        $this->data = $tahfidzs->groupBy('siswa_id')->map(function ($items) {
            $siswa = $items->first()->siswa;

            return [
                'siswa' => $siswa?->nama_lengkap ?? '-',
                'kelas' => $siswa?->kelas?->nama ?? '-',
                'total_setoran' => $items->where('jenis', 'setoran')->count(),
                'total_muroja\'ah' => $items->where('jenis', 'murojaah')->count(),
                'total_ayat' => $items->sum('jumlah_ayat'),
                'rata_rata_nilai' => round($items->avg('nilai'), 1),
                'lulus' => $items->where('status', 'lulus')->count(),
                'belum_lulus' => $items->where('status', 'belum_lulus')->count(),
            ];
        })->sortBy('kelas')->values();

        $this->summary = [
            'total_siswa' => $this->data->count(),
            'total_setoran' => $this->data->sum('total_setoran'),
            'total_murojaah' => $this->data->sum('total_muroja\'ah'),
            'total_ayat' => $this->data->sum('total_ayat'),
            'rata_rata_nilai' => round($this->data->avg('rata_rata_nilai'), 1),
        ];
    }
}
