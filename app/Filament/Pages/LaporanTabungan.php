<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanTabunganStats;
use App\Models\Kelas;
use App\Models\TabunganSiswa;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class LaporanTabungan extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'Laporan Tabungan';

    protected static ?string $slug = 'laporan/tabungan';

    protected string $view = 'filament.pages.laporan-tabungan';

    public ?int $kelas_id = null;

    public ?string $tanggal_mulai = null;

    public ?string $tanggal_selesai = null;

    public Collection $data;

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Tabungan Siswa';
    }

    public function mount(): void
    {
        $this->tanggal_mulai = now()->startOfMonth()->format('Y-m-d');
        $this->tanggal_selesai = now()->format('Y-m-d');
        $this->data = collect();
        $this->filter();
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('kelas_id')
                    ->label('Kelas')
                    ->options(Kelas::query()->where('is_active', true)->orderBy('tingkat')->orderBy('nama')->pluck('nama', 'id'))
                    ->placeholder('Semua Kelas')
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
                DatePicker::make('tanggal_mulai')
                    ->label('Dari Tanggal')
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
                DatePicker::make('tanggal_selesai')
                    ->label('Sampai Tanggal')
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
            ])
            ->columns(3);
    }

    public function filter(): void
    {
        $query = TabunganSiswa::query()
            ->with('siswa.kelas');

        if ($this->kelas_id) {
            $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $this->kelas_id));
        }

        if ($this->tanggal_mulai) {
            $query->where('tanggal', '>=', $this->tanggal_mulai);
        }

        if ($this->tanggal_selesai) {
            $query->where('tanggal', '<=', $this->tanggal_selesai);
        }

        $tabungans = $query->orderBy('tanggal', 'desc')->get();

        // Group by siswa
        $this->data = $tabungans->groupBy('siswa_id')->map(function ($items) {
            $siswa = $items->first()->siswa;
            $lastItem = $items->sortByDesc('id')->first();

            return [
                'nis' => $siswa?->nis ?? '-',
                'nama' => $siswa?->nama_lengkap ?? '-',
                'kelas' => $siswa?->kelas?->nama ?? '-',
                'total_setor' => $items->where('jenis', 'setor')->sum('nominal'),
                'total_tarik' => $items->where('jenis', 'tarik')->sum('nominal'),
                'saldo' => $lastItem?->saldo ?? 0,
                'jml_transaksi' => $items->count(),
            ];
        })->sortBy('kelas')->values();

        $this->summary = [
            'total_siswa' => $this->data->count(),
            'total_setor' => $this->data->sum('total_setor'),
            'total_tarik' => $this->data->sum('total_tarik'),
            'total_saldo' => $this->data->sum('saldo'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanTabunganStats::make([
                'summary' => $this->summary,
            ]),
        ];
    }
}
