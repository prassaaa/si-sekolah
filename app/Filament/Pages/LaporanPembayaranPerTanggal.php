<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanPembayaranPerTanggalStats;
use App\Models\Pembayaran;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class LaporanPembayaranPerTanggal extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Pembayaran Per Tanggal';

    protected static ?string $slug = 'laporan/pembayaran-per-tanggal';

    protected string $view = 'filament.pages.laporan-pembayaran-per-tanggal';

    public ?string $tanggal_mulai = null;

    public ?string $tanggal_selesai = null;

    public Collection $data;

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Pembayaran Per Tanggal';
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
            ->columns(2);
    }

    public function filter(): void
    {
        if (! $this->tanggal_mulai || ! $this->tanggal_selesai) {
            $this->data = collect();
            $this->summary = [];

            return;
        }

        $pembayarans = Pembayaran::query()
            ->with(['tagihanSiswa.siswa.kelas', 'tagihanSiswa.jenisPembayaran', 'penerima'])
            ->where('status', 'berhasil')
            ->whereBetween('tanggal_bayar', [$this->tanggal_mulai, $this->tanggal_selesai])
            ->orderBy('tanggal_bayar')
            ->get();

        // Group by date
        $this->data = $pembayarans->groupBy(fn ($p) => $p->tanggal_bayar->format('Y-m-d'))->map(function ($items, $date) {
            return [
                'tanggal' => $date,
                'jumlah_transaksi' => $items->count(),
                'tunai' => $items->where('metode_pembayaran', 'tunai')->sum('jumlah_bayar'),
                'transfer' => $items->where('metode_pembayaran', 'transfer')->sum('jumlah_bayar'),
                'lainnya' => $items->whereNotIn('metode_pembayaran', ['tunai', 'transfer'])->sum('jumlah_bayar'),
                'total' => $items->sum('jumlah_bayar'),
            ];
        })->values();

        $allPembayarans = $pembayarans;
        $this->summary = [
            'total_transaksi' => $allPembayarans->count(),
            'total_tunai' => $allPembayarans->where('metode_pembayaran', 'tunai')->sum('jumlah_bayar'),
            'total_transfer' => $allPembayarans->where('metode_pembayaran', 'transfer')->sum('jumlah_bayar'),
            'total_lainnya' => $allPembayarans->whereNotIn('metode_pembayaran', ['tunai', 'transfer'])->sum('jumlah_bayar'),
            'grand_total' => $allPembayarans->sum('jumlah_bayar'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanPembayaranPerTanggalStats::make([
                'summary' => $this->summary,
            ]),
        ];
    }
}
