<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanKeuanganStats;
use App\Models\Pembayaran;
use App\Models\TagihanSiswa;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use UnitEnum;

class LaporanKeuangan extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $navigationLabel = 'Laporan Keuangan';

    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.laporan-keuangan';

    public ?array $data = [];

    public ?string $tanggal_mulai = null;

    public ?string $tanggal_akhir = null;

    public array $summary = [];

    public function mount(): void
    {
        $this->tanggal_mulai = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->tanggal_akhir = Carbon::now()->endOfMonth()->format('Y-m-d');

        $this->form->fill([
            'tanggal_mulai' => $this->tanggal_mulai,
            'tanggal_akhir' => $this->tanggal_akhir,
        ]);

        $this->loadReport();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('tanggal_mulai')
                    ->label('Tanggal Mulai')
                    ->required()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
                DatePicker::make('tanggal_akhir')
                    ->label('Tanggal Akhir')
                    ->required()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
            ])
            ->columns(2)
            ->statePath('data');
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
        $this->tanggal_mulai = $this->data['tanggal_mulai'] ?? null;
        $this->tanggal_akhir = $this->data['tanggal_akhir'] ?? null;

        $this->loadReport();
    }

    protected function loadReport(): void
    {
        $startDate = $this->tanggal_mulai;
        $endDate = $this->tanggal_akhir;

        $totalTagihan = TagihanSiswa::where('status', '!=', 'batal')
            ->when($startDate, fn ($q) => $q->whereDate('tanggal_tagihan', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('tanggal_tagihan', '<=', $endDate))
            ->sum('total_tagihan');

        $totalPembayaran = Pembayaran::where('status', 'berhasil')
            ->when($startDate, fn ($q) => $q->whereDate('tanggal_bayar', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('tanggal_bayar', '<=', $endDate))
            ->sum('jumlah_bayar');

        $tagihanLunas = TagihanSiswa::where('status', 'lunas')
            ->when($startDate, fn ($q) => $q->whereDate('tanggal_tagihan', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('tanggal_tagihan', '<=', $endDate))
            ->count();

        $tagihanBelumLunas = TagihanSiswa::whereIn('status', ['belum_bayar', 'sebagian'])
            ->when($startDate, fn ($q) => $q->whereDate('tanggal_tagihan', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('tanggal_tagihan', '<=', $endDate))
            ->count();

        $pembayaranPerMetode = Pembayaran::where('status', 'berhasil')
            ->when($startDate, fn ($q) => $q->whereDate('tanggal_bayar', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('tanggal_bayar', '<=', $endDate))
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
