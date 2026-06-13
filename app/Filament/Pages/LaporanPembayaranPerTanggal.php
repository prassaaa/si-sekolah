<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanPembayaranPerTanggalStats;
use App\Models\Pembayaran;
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

class LaporanPembayaranPerTanggal extends Page implements HasSchemas, HasTable
{
    use HasPageShield;
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Pembayaran Per Tanggal';

    protected static ?string $slug = 'laporan/pembayaran-per-tanggal';

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Pembayaran Per Tanggal';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    /**
     * Bangun rekap pembayaran berhasil per tanggal (dipecah per metode).
     *
     * @return Collection<int, array{
     *     tanggal: string,
     *     jumlah_transaksi: int,
     *     tunai: string,
     *     transfer: string,
     *     qris: string,
     *     virtual_account: string,
     *     lainnya: string,
     *     total: string
     * }>
     */
    public function buildData(?string $tanggalMulai, ?string $tanggalSelesai): Collection
    {
        if (! $tanggalMulai || ! $tanggalSelesai) {
            $this->summary = [];

            return collect();
        }

        $pembayarans = Pembayaran::query()
            ->with(['tagihanSiswa.siswa.kelas', 'tagihanSiswa.jenisPembayaran', 'penerima'])
            ->where('status', 'berhasil')
            ->whereBetween('tanggal_bayar', [$tanggalMulai, $tanggalSelesai])
            ->orderBy('tanggal_bayar')
            ->get();

        $data = $pembayarans->groupBy(fn ($pembayaran) => $pembayaran->tanggal_bayar->format('Y-m-d'))
            ->map(function ($items, $date): array {
                return [
                    'tanggal' => $date,
                    'jumlah_transaksi' => $items->count(),
                    'tunai' => (string) $items->where('metode_pembayaran', 'tunai')->sum('jumlah_bayar'),
                    'transfer' => (string) $items->where('metode_pembayaran', 'transfer')->sum('jumlah_bayar'),
                    'qris' => (string) $items->where('metode_pembayaran', 'qris')->sum('jumlah_bayar'),
                    'virtual_account' => (string) $items->where('metode_pembayaran', 'virtual_account')->sum('jumlah_bayar'),
                    'lainnya' => (string) $items->whereNotIn('metode_pembayaran', ['tunai', 'transfer', 'qris', 'virtual_account'])->sum('jumlah_bayar'),
                    'total' => (string) $items->sum('jumlah_bayar'),
                ];
            })->values();

        $this->summary = [
            'total_transaksi' => $pembayarans->count(),
            'total_tunai' => (string) $pembayarans->where('metode_pembayaran', 'tunai')->sum('jumlah_bayar'),
            'total_transfer' => (string) $pembayarans->where('metode_pembayaran', 'transfer')->sum('jumlah_bayar'),
            'total_qris' => (string) $pembayarans->where('metode_pembayaran', 'qris')->sum('jumlah_bayar'),
            'total_virtual_account' => (string) $pembayarans->where('metode_pembayaran', 'virtual_account')->sum('jumlah_bayar'),
            'total_lainnya' => (string) $pembayarans->whereNotIn('metode_pembayaran', ['tunai', 'transfer', 'qris', 'virtual_account'])->sum('jumlah_bayar'),
            'grand_total' => (string) $pembayarans->sum('jumlah_bayar'),
        ];

        return $data;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (array $filters): Collection {
                return $this->buildData(
                    $filters['tanggal']['tanggal_mulai'] ?? null,
                    $filters['tanggal']['tanggal_selesai'] ?? null,
                );
            })
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('jumlah_transaksi')
                    ->label('Transaksi')
                    ->alignCenter(),
                TextColumn::make('tunai')
                    ->label('Tunai')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('success'),
                TextColumn::make('transfer')
                    ->label('Transfer')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('info'),
                TextColumn::make('qris')
                    ->label('QRIS')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('info'),
                TextColumn::make('virtual_account')
                    ->label('Virtual Account')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('info'),
                TextColumn::make('lainnya')
                    ->label('Lainnya')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('warning'),
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
                            ->label('Dari Tanggal')
                            ->default(now()->startOfMonth()),
                        DatePicker::make('tanggal_selesai')
                            ->label('Sampai Tanggal')
                            ->default(now()),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['tanggal_mulai'] ?? null) {
                            $indicators[] = 'Dari: '.Carbon::parse($data['tanggal_mulai'])->translatedFormat('d M Y');
                        }

                        if ($data['tanggal_selesai'] ?? null) {
                            $indicators[] = 'Sampai: '.Carbon::parse($data['tanggal_selesai'])->translatedFormat('d M Y');
                        }

                        return $indicators;
                    }),
            ])
            ->deferFilters(false)
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada data')
            ->emptyStateDescription('Silakan pilih rentang tanggal untuk melihat data pembayaran.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanPembayaranPerTanggalStats::make([
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
        $tanggalMulai = $tanggal['tanggal_mulai'] ?? null;
        $tanggalSelesai = $tanggal['tanggal_selesai'] ?? null;

        $rows = $this->buildData($tanggalMulai, $tanggalSelesai);
        $summary = $this->summary;

        $rupiah = fn (string|int|float|null $nilai): string => 'Rp '.number_format((float) ($nilai ?? 0), 0, ',', '.');

        $baris = $rows->map(function (array $row) use ($rupiah): array {
            return [
                Carbon::parse($row['tanggal'])->format('d/m/Y'),
                (string) $row['jumlah_transaksi'],
                $rupiah($row['tunai']),
                $rupiah($row['transfer']),
                $rupiah($row['qris']),
                $rupiah($row['virtual_account']),
                $rupiah($row['lainnya']),
                $rupiah($row['total']),
            ];
        })->toArray();

        $pdf = LaporanPdfService::make()
            ->judul('LAPORAN PEMBAYARAN PER TANGGAL')
            ->periode($this->periodeLabel($tanggalMulai, $tanggalSelesai))
            ->kolom([
                'Tanggal',
                ['Transaksi', 'center'],
                ['Tunai', 'right'],
                ['Transfer', 'right'],
                ['QRIS', 'right'],
                ['Virtual Account', 'right'],
                ['Lainnya', 'right'],
                ['Total', 'right'],
            ])
            ->baris($baris)
            ->ringkasan([
                [
                    'TOTAL',
                    (string) ($summary['total_transaksi'] ?? 0),
                    $rupiah($summary['total_tunai'] ?? 0),
                    $rupiah($summary['total_transfer'] ?? 0),
                    $rupiah($summary['total_qris'] ?? 0),
                    $rupiah($summary['total_virtual_account'] ?? 0),
                    $rupiah($summary['total_lainnya'] ?? 0),
                    $rupiah($summary['grand_total'] ?? 0),
                ],
            ])
            ->landscape()
            ->render();

        $namaFile = LaporanPdfService::make()
            ->namaFile('laporan-pembayaran-per-tanggal-'.now()->format('Ymd'));

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $namaFile,
        );
    }

    private function periodeLabel(?string $tanggalMulai, ?string $tanggalSelesai): ?string
    {
        if (! $tanggalMulai || ! $tanggalSelesai) {
            return null;
        }

        return 'Periode '.Carbon::parse($tanggalMulai)->translatedFormat('d F Y')
            .' s/d '.Carbon::parse($tanggalSelesai)->translatedFormat('d F Y');
    }
}
