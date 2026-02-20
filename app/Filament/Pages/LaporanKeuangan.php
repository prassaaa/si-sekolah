<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanKeuanganStats;
use App\Models\Pembayaran;
use App\Models\TagihanSiswa;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class LaporanKeuangan extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Laporan Keuangan';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'laporan/keuangan';

    protected string $view = 'filament.pages.laporan-keuangan';

    public ?string $tanggal_mulai = null;

    public ?string $tanggal_akhir = null;

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Keuangan';
    }

    public function mount(): void
    {
        $this->tanggal_mulai = now()->startOfMonth()->format('Y-m-d');
        $this->tanggal_akhir = now()->endOfMonth()->format('Y-m-d');

        $this->filter();
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('tanggal_mulai')
                    ->label('Tanggal Mulai')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
                DatePicker::make('tanggal_akhir')
                    ->label('Tanggal Akhir')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
            ])
            ->columns(2);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanKeuanganStats::make([
                'summary' => $this->summary,
            ]),
        ];
    }

    public function filter(): void
    {
        $startDate = $this->tanggal_mulai;
        $endDate = $this->tanggal_akhir;

        $totalTagihan = TagihanSiswa::where('status', '!=', 'batal')
            ->when(
                $startDate,
                fn ($q) => $q->whereDate('tanggal_tagihan', '>=', $startDate),
            )
            ->when(
                $endDate,
                fn ($q) => $q->whereDate('tanggal_tagihan', '<=', $endDate),
            )
            ->sum('total_tagihan');

        $totalPembayaran = Pembayaran::where('status', 'berhasil')
            ->when(
                $startDate,
                fn ($q) => $q->whereDate('tanggal_bayar', '>=', $startDate),
            )
            ->when(
                $endDate,
                fn ($q) => $q->whereDate('tanggal_bayar', '<=', $endDate),
            )
            ->sum('jumlah_bayar');

        $tagihanLunas = TagihanSiswa::where('status', 'lunas')
            ->when(
                $startDate,
                fn ($q) => $q->whereDate('tanggal_tagihan', '>=', $startDate),
            )
            ->when(
                $endDate,
                fn ($q) => $q->whereDate('tanggal_tagihan', '<=', $endDate),
            )
            ->count();

        $tagihanBelumLunas = TagihanSiswa::whereIn('status', [
            'belum_bayar',
            'sebagian',
        ])
            ->when(
                $startDate,
                fn ($q) => $q->whereDate('tanggal_tagihan', '>=', $startDate),
            )
            ->when(
                $endDate,
                fn ($q) => $q->whereDate('tanggal_tagihan', '<=', $endDate),
            )
            ->count();

        $pembayaranPerMetode = Pembayaran::where('status', 'berhasil')
            ->when(
                $startDate,
                fn ($q) => $q->whereDate('tanggal_bayar', '>=', $startDate),
            )
            ->when(
                $endDate,
                fn ($q) => $q->whereDate('tanggal_bayar', '<=', $endDate),
            )
            ->selectRaw('metode_pembayaran, SUM(jumlah_bayar) as total')
            ->groupBy('metode_pembayaran')
            ->pluck('total', 'metode_pembayaran')
            ->toArray();

        $this->summary = [
            'total_tagihan' => $totalTagihan,
            'total_pembayaran' => $totalPembayaran,
            'tagihan_lunas' => $tagihanLunas,
            'tagihan_belum_lunas' => $tagihanBelumLunas,
            'pembayaran_per_metode' => $pembayaranPerMetode,
        ];
    }
}
