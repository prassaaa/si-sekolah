<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanUnitPosStats;
use App\Models\Pembayaran;
use App\Models\UnitPos;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class LaporanUnitPos extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Laporan Unit POS';

    protected static ?string $slug = 'laporan/unit-pos';

    protected string $view = 'filament.pages.laporan-unit-pos';

    public ?int $unit_pos_id = null;

    public ?string $tanggal_mulai = null;

    public ?string $tanggal_selesai = null;

    public Collection $data;

    public Collection $transaksiData;

    public array $summary = [];

    public ?string $unitPosNama = null;

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Transaksi Unit POS';
    }

    public function mount(): void
    {
        $this->tanggal_mulai = now()->startOfMonth()->format('Y-m-d');
        $this->tanggal_selesai = now()->format('Y-m-d');
        $this->data = collect();
        $this->transaksiData = collect();
        $this->filter();
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('unit_pos_id')
                    ->label('Unit POS')
                    ->options(UnitPos::query()->where('is_active', true)->pluck('nama', 'id'))
                    ->placeholder('Semua Unit')
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
                DatePicker::make('tanggal_mulai')
                    ->label('Dari Tanggal')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
                DatePicker::make('tanggal_selesai')
                    ->label('Sampai Tanggal')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
            ])
            ->columns(3);
    }

    public function filter(): void
    {
        if (! $this->tanggal_mulai || ! $this->tanggal_selesai) {
            $this->data = collect();
            $this->transaksiData = collect();
            $this->summary = [];

            return;
        }

        $this->unitPosNama = $this->unit_pos_id
            ? UnitPos::find($this->unit_pos_id)?->nama
            : 'Semua Unit';

        // Get all active unit pos for summary
        $units = UnitPos::query()
            ->where('is_active', true)
            ->when($this->unit_pos_id, fn ($q) => $q->where('id', $this->unit_pos_id))
            ->get();

        // Query pembayaran with unit_pos filter
        $query = Pembayaran::query()
            ->with(['tagihanSiswa.siswa', 'tagihanSiswa.jenisPembayaran'])
            ->where('status', 'berhasil')
            ->whereBetween('tanggal_bayar', [$this->tanggal_mulai, $this->tanggal_selesai])
            ->when($this->unit_pos_id, fn ($q) => $q->where('unit_pos_id', $this->unit_pos_id));

        $pembayarans = $query->orderBy('tanggal_bayar', 'desc')->get();

        // Summary per unit
        $this->data = $units->map(function ($unit) use ($pembayarans) {
            $unitPembayarans = $pembayarans->where('unit_pos_id', $unit->id);

            return [
                'kode' => $unit->kode,
                'nama' => $unit->nama,
                'alamat' => $unit->alamat ?? '-',
                'total_transaksi' => $unitPembayarans->count(),
                'total_nominal' => $unitPembayarans->sum('jumlah_bayar'),
            ];
        });

        // Detail transaksi
        $this->transaksiData = $pembayarans->map(fn ($p) => [
            'tanggal' => $p->tanggal_bayar->format('d/m/Y'),
            'nomor_transaksi' => $p->nomor_transaksi,
            'siswa' => $p->tagihanSiswa?->siswa?->nama_lengkap ?? '-',
            'jenis' => $p->tagihanSiswa?->jenisPembayaran?->nama ?? '-',
            'metode' => $p->metode_info,
            'nominal' => $p->jumlah_bayar,
        ]);

        $this->summary = [
            'total_unit' => $units->count(),
            'total_transaksi' => $pembayarans->count(),
            'total_nominal' => $pembayarans->sum('jumlah_bayar'),
            'rata_rata' => $pembayarans->count() > 0 ? $pembayarans->sum('jumlah_bayar') / $pembayarans->count() : 0,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanUnitPosStats::make([
                'summary' => $this->summary,
                'unitPosNama' => $this->unitPosNama,
            ]),
        ];
    }
}
