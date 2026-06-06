<?php

namespace App\Filament\Pages;

use App\Models\JurnalUmum;
use App\Services\Accounting\FinancialService;
use Carbon\Carbon;
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

class LabaRugi extends Page implements HasSchemas, HasTable
{
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static \UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 4;

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

                return $data->values();
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

    /**
     * Aggregate per-account ledger movement for a given akun tipe over a
     * period using SQL SUM + GROUP BY (instead of pulling rows into PHP).
     *
     * For pendapatan (credit-normal) the nominal is SUM(kredit) - SUM(debit);
     * for beban (debit-normal) it is SUM(debit) - SUM(kredit).
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
