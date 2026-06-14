<?php

namespace App\Filament\Pages;

use App\Models\JurnalUmum;
use App\Services\Accounting\FinancialService;
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
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LabaRugi extends Page implements HasSchemas, HasTable
{
    use HasPageShield;
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static \UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 50;

    protected static ?string $title = 'Laporan Laba Rugi';

    protected static ?string $navigationLabel = 'Laba Rugi';

    public float $totalPendapatan = 0;

    public float $totalBeban = 0;

    public float $labaRugi = 0;

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Laba Rugi';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (array $filters): Collection {
                $tanggalMulai = $filters['tanggal']['tanggal_mulai'] ?? null;
                $tanggalAkhir = $filters['tanggal']['tanggal_akhir'] ?? null;
                $kategori = $filters['kategori']['value'] ?? null;

                return $this->buildRows($tanggalMulai, $tanggalAkhir, $kategori);
            })
            ->columns([
                TextColumn::make('akun')
                    ->label('Akun'),
                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pendapatan' => 'success',
                        'Beban' => 'danger',
                        'Total' => 'primary',
                        'Laba' => 'success',
                        'Rugi' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->alignEnd()
                    ->weight('bold'),
            ])
            ->filters([
                SelectFilter::make('kategori')
                    ->label('Kategori')
                    ->options([
                        'pendapatan' => 'Pendapatan',
                        'beban' => 'Beban',
                    ]),
                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('tanggal_mulai')
                            ->label('Tanggal Mulai')
                            ->default(now()->startOfMonth()),
                        DatePicker::make('tanggal_akhir')
                            ->label('Tanggal Akhir')
                            ->default(now()),
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
            ->emptyStateDescription('Silakan pilih rentang tanggal untuk melihat laporan laba rugi.')
            ->emptyStateIcon('heroicon-o-inbox');
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

    /**
     * Build the laba rugi rows shown on screen AND exported to PDF.
     *
     * Returns the per-account rincian (pendapatan/beban) followed by the
     * synthetic TOTAL PENDAPATAN, TOTAL BEBAN and LABA (RUGI) BERSIH rows so the
     * computed totals are actually rendered (temuan #77). Totals come from
     * FinancialService (the single source of truth, trashed-inclusive) so the
     * total always equals the sum of the rincian — see #71/#76. As a side
     * effect, the public $total* properties are refreshed for any external
     * assertion.
     *
     * @return Collection<int, array{akun: string, kategori: string, nominal: float}>
     */
    public function buildRows(?string $tanggalMulai, ?string $tanggalAkhir, ?string $kategori = null): Collection
    {
        if (! $tanggalMulai || ! $tanggalAkhir) {
            $this->totalPendapatan = 0;
            $this->totalBeban = 0;
            $this->labaRugi = 0;

            return collect();
        }

        $data = collect();

        if (! $kategori || $kategori === 'pendapatan') {
            $data = $data->merge(
                $this->aggregateByAkun('pendapatan', 'Pendapatan', $tanggalMulai, $tanggalAkhir),
            );
        }

        if (! $kategori || $kategori === 'beban') {
            $data = $data->merge(
                $this->aggregateByAkun('beban', 'Beban', $tanggalMulai, $tanggalAkhir),
            );
        }

        $financial = app(FinancialService::class);
        $this->totalPendapatan = (float) $financial->totalPendapatan($tanggalMulai, $tanggalAkhir);
        $this->totalBeban = (float) $financial->totalBeban($tanggalMulai, $tanggalAkhir);
        $this->labaRugi = (float) $financial->netIncome($tanggalMulai, $tanggalAkhir);

        if (! $kategori || $kategori === 'pendapatan') {
            $data->push([
                'akun' => 'TOTAL PENDAPATAN',
                'kategori' => 'Total',
                'nominal' => $this->totalPendapatan,
            ]);
        }

        if (! $kategori || $kategori === 'beban') {
            $data->push([
                'akun' => 'TOTAL BEBAN',
                'kategori' => 'Total',
                'nominal' => $this->totalBeban,
            ]);
        }

        if (! $kategori) {
            $data->push([
                'akun' => 'LABA (RUGI) BERSIH',
                'kategori' => $this->labaRugi >= 0 ? 'Laba' : 'Rugi',
                'nominal' => $this->labaRugi,
            ]);
        }

        return $data->values();
    }

    public function cetakPdf(): StreamedResponse
    {
        $filters = $this->getTableFilterState('tanggal') ?? [];
        $kategoriFilter = $this->getTableFilterState('kategori') ?? [];

        $tanggalMulai = $filters['tanggal_mulai'] ?? null;
        $tanggalAkhir = $filters['tanggal_akhir'] ?? null;
        $kategori = $kategoriFilter['value'] ?? null;

        $rows = $this->buildRows($tanggalMulai, $tanggalAkhir, $kategori);

        $isTotal = fn (array $row): bool => in_array($row['kategori'], ['Total', 'Laba', 'Rugi'], true);

        $baris = $rows
            ->reject($isTotal)
            ->map(fn (array $row): array => [
                $row['akun'],
                $row['kategori'],
                number_format((float) $row['nominal'], 0, ',', '.'),
            ])
            ->values()
            ->all();

        $ringkasan = $rows
            ->filter($isTotal)
            ->map(fn (array $row): array => [
                $row['akun'],
                '',
                number_format((float) $row['nominal'], 0, ',', '.'),
            ])
            ->values()
            ->all();

        $pdf = LaporanPdfService::make()
            ->judul('LAPORAN LABA RUGI')
            ->periode($this->labelPeriode($tanggalMulai, $tanggalAkhir))
            ->kolom(['Akun', 'Kategori', ['Nominal (Rp)', 'right']])
            ->baris($baris)
            ->ringkasan($ringkasan)
            ->render();

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            LaporanPdfService::make()->namaFile('laba-rugi-'.($tanggalAkhir ?? now()->toDateString())),
        );
    }

    private function labelPeriode(?string $tanggalMulai, ?string $tanggalAkhir): string
    {
        if (! $tanggalMulai || ! $tanggalAkhir) {
            return 'Semua Periode';
        }

        return 'Periode '.Carbon::parse($tanggalMulai)->translatedFormat('d M Y')
            .' s.d. '.Carbon::parse($tanggalAkhir)->translatedFormat('d M Y');
    }

    /**
     * Aggregate per-account ledger movement for a given akun tipe over a
     * period using SQL SUM + GROUP BY (instead of pulling rows into PHP).
     *
     * For pendapatan (credit-normal) the nominal is SUM(kredit) - SUM(debit);
     * for beban (debit-normal) it is SUM(debit) - SUM(kredit). The join is left
     * unfiltered on akuns.deleted_at so trashed accounts that still carry
     * journal history are included — matching FinancialService so the rincian
     * always sums to the total (temuan #71/#76).
     *
     * @return Collection<int, array{akun: string, kategori: string, nominal: float}>
     */
    private function aggregateByAkun(string $tipe, string $kategoriLabel, string $tanggalMulai, string $tanggalAkhir): Collection
    {
        $nominalExpression = $tipe === 'pendapatan'
            ? 'COALESCE(SUM(jurnal_umums.kredit), 0) - COALESCE(SUM(jurnal_umums.debit), 0)'
            : 'COALESCE(SUM(jurnal_umums.debit), 0) - COALESCE(SUM(jurnal_umums.kredit), 0)';

        return JurnalUmum::query()
            ->join('akuns', 'akuns.id', '=', 'jurnal_umums.akun_id')
            ->where('akuns.tipe', $tipe)
            ->whereBetween('jurnal_umums.tanggal', [$tanggalMulai, $tanggalAkhir])
            ->groupBy('akuns.id', 'akuns.nama')
            ->selectRaw('akuns.nama as akun_nama, '.$nominalExpression.' as nominal')
            ->get()
            ->map(fn ($row) => [
                'akun' => $row->akun_nama ?? '-',
                'kategori' => $kategoriLabel,
                'nominal' => (float) $row->nominal,
            ]);
    }
}
