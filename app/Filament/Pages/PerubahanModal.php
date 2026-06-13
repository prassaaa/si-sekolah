<?php

namespace App\Filament\Pages;

use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Services\Accounting\FinancialService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
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
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class PerubahanModal extends Page implements HasSchemas, HasTable
{
    use HasPageShield;
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;

    protected static \UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 5;

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

                if (! $tanggalMulai || ! $tanggalAkhir) {
                    return collect();
                }

                $financial = app(FinancialService::class);

                $akunModalIds = Akun::where('tipe', 'ekuitas')->pluck('id')->all();

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
