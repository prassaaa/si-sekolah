<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanPembayaranPerKelasStats;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\TagihanSiswa;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class LaporanPembayaranPerKelas extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Pembayaran Per Kelas';

    protected static ?string $slug = 'laporan/pembayaran-per-kelas';

    protected string $view = 'filament.pages.laporan-pembayaran-per-kelas';

    public ?int $semester_id = null;

    public ?int $kelas_id = null;

    public Collection $data;

    public array $summary = [];

    public ?string $kelasNama = null;

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Pembayaran Per Kelas';
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
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
            ])
            ->columns(2);
    }

    public function filter(): void
    {
        if (! $this->semester_id || ! $this->kelas_id) {
            $this->data = collect();
            $this->summary = [];
            $this->kelasNama = null;

            return;
        }

        $this->kelasNama = Kelas::find($this->kelas_id)?->nama;

        $tagihans = TagihanSiswa::query()
            ->with(['siswa', 'jenisPembayaran', 'pembayarans'])
            ->where('semester_id', $this->semester_id)
            ->whereHas('siswa', fn ($q) => $q->where('kelas_id', $this->kelas_id))
            ->get();

        // Group by siswa
        $this->data = $tagihans->groupBy('siswa_id')->map(function ($items) {
            $siswa = $items->first()->siswa;

            return [
                'nis' => $siswa?->nis ?? '-',
                'nama' => $siswa?->nama_lengkap ?? '-',
                'total_tagihan' => $items->sum('total_tagihan'),
                'total_terbayar' => $items->sum('total_terbayar'),
                'sisa' => $items->sum('sisa_tagihan'),
                'status' => $items->every(fn ($t) => $t->status === 'lunas') ? 'Lunas' : 'Belum Lunas',
            ];
        })->sortBy('nama')->values();

        $this->summary = [
            'total_siswa' => $this->data->count(),
            'total_tagihan' => $this->data->sum('total_tagihan'),
            'total_terbayar' => $this->data->sum('total_terbayar'),
            'total_sisa' => $this->data->sum('sisa'),
            'lunas' => $this->data->where('status', 'Lunas')->count(),
            'belum_lunas' => $this->data->where('status', 'Belum Lunas')->count(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanPembayaranPerKelasStats::make([
                'summary' => $this->summary,
                'kelasNama' => $this->kelasNama,
            ]),
        ];
    }
}
