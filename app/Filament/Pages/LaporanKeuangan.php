<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanKeuanganStats;
use App\Models\Pembayaran;
use App\Models\TagihanSiswa;
use App\Services\Accounting\LaporanPdfService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanKeuangan extends Page implements HasSchemas, HasTable
{
    use HasPageShield;
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Laporan Keuangan';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'laporan/keuangan';

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Keuangan';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    /**
     * Bangun rekap laporan keuangan untuk satu periode.
     *
     * Laporan ini memakai DUA basis tanggal berbeda yang diberi label eksplisit
     * agar tidak disalahartikan sebagai satu angka yang dapat langsung
     * dibandingkan (#72/#80):
     *   - "Tagihan terbit periode ini": agregat TagihanSiswa berbasis
     *     `tanggal_tagihan` (kapan tagihan diterbitkan).
     *   - "Pembayaran diterima periode ini": agregat Pembayaran berbasis
     *     `tanggal_bayar` (kapan kas diterima).
     *
     * Tagihan dan pembayaran atas tagihan berstatus "batal" dikecualikan dari
     * seluruh agregat.
     *
     * @return Collection<int, array{metode: string, jumlah: int, total: string}>
     */
    public function buildData(?string $startDate, ?string $endDate): Collection
    {
        $totalTagihan = TagihanSiswa::where('status', '!=', 'batal')
            ->when($startDate, fn ($query) => $query->whereDate('tanggal_tagihan', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('tanggal_tagihan', '<=', $endDate))
            ->sum('total_tagihan');

        $totalPembayaran = Pembayaran::where('status', 'berhasil')
            ->whereHas('tagihanSiswa', fn ($query) => $query->where('status', '!=', 'batal'))
            ->when($startDate, fn ($query) => $query->whereDate('tanggal_bayar', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('tanggal_bayar', '<=', $endDate))
            ->sum('jumlah_bayar');

        $tagihanLunas = TagihanSiswa::where('status', 'lunas')
            ->when($startDate, fn ($query) => $query->whereDate('tanggal_tagihan', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('tanggal_tagihan', '<=', $endDate))
            ->count();

        $tagihanBelumLunas = TagihanSiswa::whereIn('status', ['belum_bayar', 'sebagian'])
            ->when($startDate, fn ($query) => $query->whereDate('tanggal_tagihan', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('tanggal_tagihan', '<=', $endDate))
            ->count();

        $pembayaranPerMetode = Pembayaran::where('status', 'berhasil')
            ->whereHas('tagihanSiswa', fn ($query) => $query->where('status', '!=', 'batal'))
            ->when($startDate, fn ($query) => $query->whereDate('tanggal_bayar', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('tanggal_bayar', '<=', $endDate))
            ->selectRaw('metode_pembayaran, SUM(jumlah_bayar) as total, COUNT(*) as jumlah')
            ->groupBy('metode_pembayaran')
            ->get();

        $this->summary = [
            'total_tagihan' => (string) $totalTagihan,
            'total_pembayaran' => (string) $totalPembayaran,
            'tagihan_lunas' => $tagihanLunas,
            'tagihan_belum_lunas' => $tagihanBelumLunas,
        ];

        return $pembayaranPerMetode->values()->map(function ($item): array {
            return [
                'metode' => match ($item->metode_pembayaran) {
                    'tunai' => 'Tunai',
                    'transfer' => 'Transfer Bank',
                    'qris' => 'QRIS',
                    'virtual_account' => 'Virtual Account',
                    default => ucfirst($item->metode_pembayaran),
                },
                'jumlah' => (int) $item->jumlah,
                'total' => (string) $item->total,
            ];
        });
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (array $filters): Collection {
                return $this->buildData(
                    $filters['tanggal']['tanggal_mulai'] ?? null,
                    $filters['tanggal']['tanggal_akhir'] ?? null,
                );
            })
            ->columns([
                TextColumn::make('metode')
                    ->label('Metode Pembayaran')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Tunai' => 'success',
                        'Transfer Bank' => 'info',
                        'QRIS' => 'primary',
                        'Virtual Account' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('jumlah')
                    ->label('Jumlah Transaksi')
                    ->alignCenter(),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->alignEnd()
                    ->weight('bold'),
            ])
            ->filters([
                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('tanggal_mulai')
                            ->label('Tanggal Mulai')
                            ->default(now()->startOfMonth()),
                        DatePicker::make('tanggal_akhir')
                            ->label('Tanggal Akhir')
                            ->default(now()->endOfMonth()),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['tanggal_mulai'] ?? null) {
                            $indicators[] = 'Dari: '.Carbon::parse($data['tanggal_mulai'])->translatedFormat('d M Y');
                        }

                        if ($data['tanggal_akhir'] ?? null) {
                            $indicators[] = 'Sampai: '.Carbon::parse($data['tanggal_akhir'])->translatedFormat('d M Y');
                        }

                        return $indicators;
                    }),
            ])
            ->deferFilters(false)
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada data')
            ->emptyStateDescription('Silakan pilih periode untuk melihat laporan keuangan.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanKeuanganStats::make([
                'summary' => $this->summary,
            ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cetakPdf')
                ->label('Cetak PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(fn (): StreamedResponse => $this->cetakPdf()),
        ];
    }

    private function cetakPdf(): StreamedResponse
    {
        $tanggal = $this->getTableFilterState('tanggal') ?? [];
        $startDate = $tanggal['tanggal_mulai'] ?? null;
        $endDate = $tanggal['tanggal_akhir'] ?? null;

        $rows = $this->buildData($startDate, $endDate);
        $summary = $this->summary;

        $baris = $rows->map(function (array $row): array {
            return [
                $row['metode'],
                (string) $row['jumlah'],
                'Rp '.number_format((float) $row['total'], 0, ',', '.'),
            ];
        })->toArray();

        $pdf = LaporanPdfService::make()
            ->judul('LAPORAN KEUANGAN')
            ->periode($this->periodeLabel($startDate, $endDate))
            ->kolom([
                'Metode Pembayaran',
                ['Jumlah Transaksi', 'center'],
                ['Total Diterima', 'right'],
            ])
            ->baris($baris)
            ->ringkasan([
                [
                    'TOTAL PEMBAYARAN DITERIMA',
                    '',
                    'Rp '.number_format((float) ($summary['total_pembayaran'] ?? 0), 0, ',', '.'),
                ],
            ])
            ->catatan(
                'Tagihan terbit periode ini: Rp '.number_format((float) ($summary['total_tagihan'] ?? 0), 0, ',', '.')
                .' (berbasis tanggal tagihan). Pembayaran diterima periode ini: Rp '
                .number_format((float) ($summary['total_pembayaran'] ?? 0), 0, ',', '.')
                .' (berbasis tanggal bayar). Tagihan/pembayaran berstatus batal dikecualikan.'
            )
            ->render();

        $namaFile = LaporanPdfService::make()
            ->namaFile('laporan-keuangan-'.now()->format('Ymd'));

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $namaFile,
        );
    }

    private function periodeLabel(?string $startDate, ?string $endDate): ?string
    {
        if (! $startDate && ! $endDate) {
            return null;
        }

        $mulai = $startDate ? Carbon::parse($startDate)->translatedFormat('d F Y') : '...';
        $akhir = $endDate ? Carbon::parse($endDate)->translatedFormat('d F Y') : '...';

        return 'Periode '.$mulai.' s/d '.$akhir;
    }
}
