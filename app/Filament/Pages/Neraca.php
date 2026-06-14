<?php

namespace App\Filament\Pages;

use App\Models\Akun;
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

class Neraca extends Page implements HasSchemas, HasTable
{
    use HasPageShield;
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static \UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 60;

    protected static ?string $title = 'Neraca';

    protected static ?string $navigationLabel = 'Neraca';

    public function getTitle(): string|Htmlable
    {
        return 'Neraca';
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
                $tanggal = $filters['tanggal']['tanggal'] ?? null;
                $tipe = $filters['tipe']['value'] ?? null;

                return $this->buildRows($tanggal, $tipe);
            })
            ->columns([
                TextColumn::make('akun')
                    ->label('Akun'),
                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aset' => 'success',
                        'Kewajiban' => 'danger',
                        'Modal' => 'info',
                        'Total' => 'primary',
                        'Seimbang' => 'success',
                        'Selisih' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('saldo')
                    ->label('Saldo')
                    ->money('IDR')
                    ->alignEnd()
                    ->weight('bold'),
            ])
            ->filters([
                SelectFilter::make('tipe')
                    ->label('Tipe')
                    ->options([
                        'aset' => 'Aset',
                        'liabilitas' => 'Kewajiban',
                        'ekuitas' => 'Modal',
                    ]),
                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('tanggal')
                            ->label('Per Tanggal')
                            ->default(now()),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (! ($data['tanggal'] ?? null)) {
                            return null;
                        }

                        return 'Per: '.Carbon::parse($data['tanggal'])->translatedFormat('d M Y');
                    }),
            ])
            ->deferFilters(false)
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada data')
            ->emptyStateDescription('Silakan pilih tanggal untuk melihat neraca.')
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
     * Build the neraca rows shown on screen AND exported to PDF.
     *
     * Account balances come from FinancialService::saldoPerAkun (snapshot
     * semantics, trashed-inclusive). The account list itself is fetched
     * withTrashed() so a soft-deleted account that still carries a balance is
     * both summed and rendered, keeping TOTAL ASET == TOTAL KEWAJIBAN+MODAL
     * (temuan #37/#71/#76). A synthetic "Laba (Rugi) Berjalan" equity line is
     * appended so the neraca stays SEIMBANG (T2).
     *
     * @return Collection<int, array{akun: string, tipe: string, saldo: string}>
     */
    public function buildRows(?string $tanggal, ?string $tipe = null): Collection
    {
        if (! $tanggal) {
            return collect();
        }

        $akuns = Akun::withTrashed()
            ->whereIn('tipe', ['aset', 'liabilitas', 'ekuitas'])
            ->orderBy('kode')
            ->get();

        $saldoPerAkun = app(FinancialService::class)
            ->saldoPerAkun($akuns->pluck('id')->all(), $tanggal);

        $visibleAkuns = $tipe
            ? $akuns->where('tipe', $tipe)
            : $akuns;

        $rows = $visibleAkuns->map(function ($akun) use ($saldoPerAkun) {
            $saldo = $saldoPerAkun[$akun->id] ?? '0';

            if (bccomp($saldo, '0', 2) === 0) {
                return null;
            }

            return [
                'akun' => $akun->nama,
                'tipe' => match ($akun->tipe) {
                    'aset' => 'Aset',
                    'liabilitas' => 'Kewajiban',
                    'ekuitas' => 'Modal',
                    default => ucfirst($akun->tipe),
                },
                'saldo' => $saldo,
            ];
        })->filter()->values();

        $totalAset = '0';
        $totalLiabilitasEkuitas = '0';

        foreach ($akuns as $akun) {
            $saldo = $saldoPerAkun[$akun->id] ?? '0';

            if ($akun->tipe === 'aset') {
                $totalAset = bcadd($totalAset, $saldo, 2);
            } else {
                $totalLiabilitasEkuitas = bcadd($totalLiabilitasEkuitas, $saldo, 2);
            }
        }

        $labaBerjalan = $this->labaBerjalan($tanggal);
        $totalLiabilitasEkuitas = bcadd($totalLiabilitasEkuitas, $labaBerjalan, 2);

        if ((! $tipe || $tipe === 'ekuitas') && bccomp($labaBerjalan, '0', 2) !== 0) {
            $rows->push([
                'akun' => 'Laba (Rugi) Berjalan',
                'tipe' => 'Modal',
                'saldo' => $labaBerjalan,
            ]);
        }

        $selisih = bcsub($totalAset, $totalLiabilitasEkuitas, 2);
        $balanced = bccomp($selisih, '0', 2) === 0;

        if (! $tipe) {
            $rows->push([
                'akun' => 'TOTAL ASET',
                'tipe' => 'Total',
                'saldo' => $totalAset,
            ]);
            $rows->push([
                'akun' => 'TOTAL KEWAJIBAN + MODAL',
                'tipe' => 'Total',
                'saldo' => $totalLiabilitasEkuitas,
            ]);
            $rows->push([
                'akun' => $balanced ? 'SEIMBANG (Balanced)' : 'TIDAK SEIMBANG (Selisih)',
                'tipe' => $balanced ? 'Seimbang' : 'Selisih',
                'saldo' => $selisih,
            ]);
        }

        return $rows;
    }

    public function cetakPdf(): StreamedResponse
    {
        $filters = $this->getTableFilterState('tanggal') ?? [];
        $tipeFilter = $this->getTableFilterState('tipe') ?? [];

        $tanggal = $filters['tanggal'] ?? null;
        $tipe = $tipeFilter['value'] ?? null;

        $rows = $this->buildRows($tanggal, $tipe);

        $isTotal = fn (array $row): bool => in_array($row['tipe'], ['Total', 'Seimbang', 'Selisih'], true);

        $baris = $rows
            ->reject($isTotal)
            ->map(fn (array $row): array => [
                $row['akun'],
                $row['tipe'],
                number_format((float) $row['saldo'], 0, ',', '.'),
            ])
            ->values()
            ->all();

        $ringkasan = $rows
            ->filter($isTotal)
            ->map(fn (array $row): array => [
                $row['akun'],
                '',
                number_format((float) $row['saldo'], 0, ',', '.'),
            ])
            ->values()
            ->all();

        $pdf = LaporanPdfService::make()
            ->judul('NERACA')
            ->periode($tanggal ? 'Per '.Carbon::parse($tanggal)->translatedFormat('d F Y') : 'Per Tanggal')
            ->kolom(['Akun', 'Tipe', ['Saldo (Rp)', 'right']])
            ->baris($baris)
            ->ringkasan($ringkasan)
            ->render();

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            LaporanPdfService::make()->namaFile('neraca-'.($tanggal ?? now()->toDateString())),
        );
    }

    /**
     * Laba (rugi) berjalan as of $tanggal: a synthetic equity line.
     *
     * Net income is accumulated from the latest saldo awal snapshot date (the
     * MAX tanggal across the selected snapshots <= $tanggal) through $tanggal.
     * ASSUMPTION: saldo awal of a tahun ajaran is entered on a single shared
     * date that already absorbed prior-period results, so accumulating net
     * income from that date forward avoids double counting. With no snapshot,
     * net income is accumulated since the beginning of the books (null start).
     */
    private function labaBerjalan(string $tanggal): string
    {
        $financial = app(FinancialService::class);
        $awalLaba = $financial->latestSnapshotDate($tanggal);

        return $financial->netIncome($awalLaba, $tanggal);
    }
}
