<?php

namespace App\Filament\Pages;

use App\Models\Akun;
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
use Illuminate\Support\Facades\DB;

class Neraca extends Page implements HasSchemas, HasTable
{
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static \UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 6;

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

                if (! $tanggal) {
                    return collect();
                }

                $saldoPerAkun = $this->calculateSaldoPerAkun($tanggal);

                $akuns = Akun::whereIn('tipe', ['aset', 'liabilitas', 'ekuitas'])
                    ->when($tipe, fn ($q) => $q->where('tipe', $tipe))
                    ->orderBy('kode')
                    ->get();

                $rows = $akuns->map(function ($akun) use ($saldoPerAkun) {
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

    /**
     * Balance per account as of a date, driven by each account's posisi_normal.
     *
     * A debit-normal account's balance is opening + (debit - kredit); a
     * credit-normal account's balance is opening + (kredit - debit). Soft
     * deleted saldo_awals and jurnal_umums rows are excluded.
     *
     * @return array<int, string>
     */
    private function calculateSaldoPerAkun(string $tanggal): array
    {
        $saldoAwal = DB::table('saldo_awals')
            ->select('akun_id', DB::raw('SUM(saldo) as total'))
            ->where('tanggal', '<=', $tanggal)
            ->whereNull('deleted_at')
            ->groupBy('akun_id')
            ->pluck('total', 'akun_id')
            ->toArray();

        $jurnal = DB::table('jurnal_umums')
            ->select(
                'akun_id',
                DB::raw(
                    'SUM(debit) as total_debit, SUM(kredit) as total_kredit',
                ),
            )
            ->where('tanggal', '<=', $tanggal)
            ->whereNull('deleted_at')
            ->groupBy('akun_id')
            ->get()
            ->keyBy('akun_id');

        $akuns = Akun::whereIn('tipe', [
            'aset',
            'liabilitas',
            'ekuitas',
        ])->get();

        $result = [];
        foreach ($akuns as $akun) {
            $awal = (string) ($saldoAwal[$akun->id] ?? '0');
            $row = $jurnal[$akun->id] ?? null;

            $debit = (string) ($row->total_debit ?? '0');
            $kredit = (string) ($row->total_kredit ?? '0');

            $jurnalSaldo = $akun->posisi_normal === 'debit'
                ? bcsub($debit, $kredit, 2)
                : bcsub($kredit, $debit, 2);

            $result[$akun->id] = bcadd($awal, $jurnalSaldo, 2);
        }

        return $result;
    }
}
