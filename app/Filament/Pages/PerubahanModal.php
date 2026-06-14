<?php

namespace App\Filament\Pages;

use App\Models\Akun;
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
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PerubahanModal extends Page implements HasSchemas, HasTable
{
    use HasPageShield;
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;

    protected static \UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 70;

    protected static ?string $title = 'Perubahan Modal';

    protected static ?string $navigationLabel = 'Perubahan Modal';

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Perubahan Modal';
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

                return $this->buildRows($tanggalMulai, $tanggalAkhir);
            })
            ->columns([
                TextColumn::make('uraian')
                    ->label('Uraian')
                    ->weight('bold'),
                TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->alignEnd()
                    ->weight('bold')
                    ->color(fn (mixed $state): string => $state >= 0 ? 'success' : 'danger'),
            ])
            ->filters([
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
            ->emptyStateDescription('Silakan pilih rentang tanggal untuk melihat perubahan modal.')
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
     * Build the perubahan modal rows shown on screen AND exported to PDF.
     *
     * Modal Awal chains from the prior period's Modal Akhir (snapshot semantics
     * via FinancialService), Laba/Rugi Periode reuses the canonical netIncome
     * so it agrees with LabaRugi, and Prive is the net debit on contra-equity
     * accounts. Ekuitas accounts are read withTrashed() for the same
     * consistency reason as the other reports (temuan #37/#71/#76).
     *
     * @return Collection<int, array{uraian: string, nominal: string}>
     */
    public function buildRows(?string $tanggalMulai, ?string $tanggalAkhir): Collection
    {
        if (! $tanggalMulai || ! $tanggalAkhir) {
            return collect();
        }

        $financial = app(FinancialService::class);

        $akunModalIds = Akun::withTrashed()->where('tipe', 'ekuitas')->pluck('id')->all();

        $sebelumMulai = Carbon::parse($tanggalMulai)->subDay()->toDateString();

        $saldoEkuitas = $financial->saldoAwalPeriodePerAkun($akunModalIds, $tanggalMulai);
        $modalAkun = array_reduce(
            $saldoEkuitas,
            fn (string $carry, string $saldo): string => bcadd($carry, $saldo, 2),
            '0',
        );

        $awalLaba = $financial->latestSnapshotDate($tanggalMulai);
        $labaKumulatif = $financial->netIncome($awalLaba, $sebelumMulai);

        $modalAwal = bcadd($modalAkun, $labaKumulatif, 2);

        $labaRugi = $financial->netIncome($tanggalMulai, $tanggalAkhir);

        $prive = $this->calculatePrive($tanggalMulai, $tanggalAkhir);

        $modalAkhir = bcsub(bcadd($modalAwal, $labaRugi, 2), $prive, 2);

        return collect([
            0 => ['uraian' => 'Modal Awal', 'nominal' => $modalAwal],
            1 => ['uraian' => 'Laba/Rugi Periode', 'nominal' => $labaRugi],
            2 => ['uraian' => 'Prive (Pengambilan)', 'nominal' => $prive],
            3 => ['uraian' => 'Modal Akhir', 'nominal' => $modalAkhir],
        ]);
    }

    public function cetakPdf(): StreamedResponse
    {
        $tanggalFilter = $this->getTableFilterState('tanggal') ?? [];
        $tanggalMulai = $tanggalFilter['tanggal_mulai'] ?? null;
        $tanggalAkhir = $tanggalFilter['tanggal_akhir'] ?? null;

        $rows = $this->buildRows($tanggalMulai, $tanggalAkhir);

        $baris = $rows
            ->map(fn (array $row): array => [
                $row['uraian'],
                number_format((float) $row['nominal'], 0, ',', '.'),
            ])
            ->values()
            ->all();

        $periode = ($tanggalMulai && $tanggalAkhir)
            ? 'Periode '.Carbon::parse($tanggalMulai)->translatedFormat('d M Y')
                .' s.d. '.Carbon::parse($tanggalAkhir)->translatedFormat('d M Y')
            : 'Semua Periode';

        $pdf = LaporanPdfService::make()
            ->judul('LAPORAN PERUBAHAN MODAL')
            ->periode($periode)
            ->kolom(['Uraian', ['Nominal (Rp)', 'right']])
            ->baris($baris)
            ->render();

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            LaporanPdfService::make()->namaFile('perubahan-modal-'.($tanggalAkhir ?? now()->toDateString())),
        );
    }

    /**
     * Prive (owner withdrawal) for a period.
     *
     * ASSUMPTION: There is no dedicated "prive" flag in the schema. We identify
     * prive accounts as ekuitas accounts that are debit-normal (a normal equity
     * account is credit-normal; a debit-normal equity account is, by accounting
     * convention, a contra-equity / drawing account such as the seeded "Prive"
     * akun with kode 3-3001). Their prive amount over the period is the net
     * debit movement (debit - kredit) on those accounts.
     */
    private function calculatePrive(string $tanggalMulai, string $tanggalAkhir): string
    {
        $akunPriveIds = Akun::query()
            ->where('tipe', 'ekuitas')
            ->where('posisi_normal', 'debit')
            ->pluck('id');

        if ($akunPriveIds->isEmpty()) {
            return '0.00';
        }

        $row = JurnalUmum::query()
            ->whereIn('akun_id', $akunPriveIds)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(kredit), 0) as total_kredit')
            ->first();

        return bcsub(
            (string) ($row->total_debit ?? '0'),
            (string) ($row->total_kredit ?? '0'),
            2,
        );
    }
}
