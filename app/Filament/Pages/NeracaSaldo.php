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
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NeracaSaldo extends Page implements HasSchemas, HasTable
{
    use HasPageShield;
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static \UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 40;

    protected static ?string $title = 'Neraca Saldo';

    protected static ?string $navigationLabel = 'Neraca Saldo';

    public function getTitle(): string|Htmlable
    {
        return 'Neraca Saldo';
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
                return $this->buildTrialBalance($filters);
            })
            ->columns([
                TextColumn::make('kode')
                    ->label('Kode'),
                TextColumn::make('nama')
                    ->label('Nama Akun'),
                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aset' => 'success',
                        'Liabilitas' => 'warning',
                        'Ekuitas' => 'info',
                        'Pendapatan' => 'primary',
                        'Beban' => 'danger',
                        'Total' => 'primary',
                        'Seimbang' => 'success',
                        'Selisih' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('debit')
                    ->label('Debit')
                    ->money('IDR')
                    ->alignEnd()
                    ->weight('bold'),
                TextColumn::make('kredit')
                    ->label('Kredit')
                    ->money('IDR')
                    ->alignEnd()
                    ->weight('bold'),
            ])
            ->filters([
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
                Filter::make('tampilkan_nol')
                    ->label('Tampilkan akun saldo 0')
                    ->toggle(),
            ])
            ->deferFilters(false)
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada data')
            ->emptyStateDescription('Silakan pilih tanggal untuk melihat neraca saldo.')
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

    public function cetakPdf(): StreamedResponse
    {
        $tanggalFilter = $this->getTableFilterState('tanggal') ?? [];
        $tanggal = $tanggalFilter['tanggal'] ?? null;

        $rows = $this->buildTrialBalance([
            'tanggal' => $tanggalFilter,
            'tampilkan_nol' => $this->getTableFilterState('tampilkan_nol') ?? [],
        ]);

        $isTotal = fn (array $row): bool => in_array($row['tipe'], ['Total', 'Seimbang', 'Selisih'], true);

        $baris = $rows
            ->reject($isTotal)
            ->map(fn (array $row): array => [
                $row['kode'],
                $row['nama'],
                $row['tipe'],
                number_format((float) $row['debit'], 0, ',', '.'),
                number_format((float) $row['kredit'], 0, ',', '.'),
            ])
            ->values()
            ->all();

        $ringkasan = $rows
            ->filter($isTotal)
            ->map(fn (array $row): array => [
                '',
                $row['nama'],
                '',
                number_format((float) $row['debit'], 0, ',', '.'),
                number_format((float) $row['kredit'], 0, ',', '.'),
            ])
            ->values()
            ->all();

        $pdf = LaporanPdfService::make()
            ->judul('NERACA SALDO')
            ->periode($tanggal ? 'Per '.Carbon::parse($tanggal)->translatedFormat('d F Y') : 'Per Tanggal')
            ->kolom(['Kode', 'Nama Akun', 'Tipe', ['Debit (Rp)', 'right'], ['Kredit (Rp)', 'right']])
            ->baris($baris)
            ->ringkasan($ringkasan)
            ->render();

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            LaporanPdfService::make()->namaFile('neraca-saldo-'.($tanggal ?? now()->toDateString())),
        );
    }

    /**
     * Build the trial balance: every account's ending balance as of the filter
     * date, placed in the Debit or Kredit column per its posisi_normal, with a
     * footer total and a SEIMBANG/SELISIH badge. Accounts with a zero balance
     * are hidden. Balances come from FinancialService::saldoPerAkun (snapshot
     * semantics) so the trial balance reconciles with Neraca and Buku Besar.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function buildTrialBalance(array $filters): Collection
    {
        $tanggal = $filters['tanggal']['tanggal'] ?? null;
        $tampilkanNol = (bool) ($filters['tampilkan_nol']['isActive'] ?? false);

        if (! $tanggal) {
            return collect();
        }

        $akuns = Akun::withTrashed()->orderBy('kode')->get();

        $saldoPerAkun = app(FinancialService::class)
            ->saldoPerAkun($akuns->pluck('id')->all(), $tanggal);

        $totalDebit = '0';
        $totalKredit = '0';

        $rows = $akuns->map(function ($akun) use ($saldoPerAkun, $tampilkanNol, &$totalDebit, &$totalKredit) {
            $saldo = $saldoPerAkun[$akun->id] ?? '0';

            if (! $tampilkanNol && bccomp($saldo, '0', 2) === 0) {
                return null;
            }

            $isDebitNormal = $akun->posisi_normal === 'debit';

            $debit = $isDebitNormal ? $saldo : '0';
            $kredit = $isDebitNormal ? '0' : $saldo;

            $totalDebit = bcadd($totalDebit, $debit, 2);
            $totalKredit = bcadd($totalKredit, $kredit, 2);

            return [
                'kode' => $akun->kode,
                'nama' => $akun->nama,
                'tipe' => match ($akun->tipe) {
                    'aset' => 'Aset',
                    'liabilitas' => 'Liabilitas',
                    'ekuitas' => 'Ekuitas',
                    'pendapatan' => 'Pendapatan',
                    'beban' => 'Beban',
                    default => ucfirst($akun->tipe),
                },
                'debit' => $debit,
                'kredit' => $kredit,
            ];
        })->filter()->values();

        $selisih = bcsub($totalDebit, $totalKredit, 2);
        $balanced = bccomp($selisih, '0', 2) === 0;

        $rows->push([
            'kode' => '',
            'nama' => 'TOTAL',
            'tipe' => 'Total',
            'debit' => $totalDebit,
            'kredit' => $totalKredit,
        ]);

        $rows->push([
            'kode' => '',
            'nama' => $balanced ? 'SEIMBANG (Balanced)' : 'TIDAK SEIMBANG (Selisih)',
            'tipe' => $balanced ? 'Seimbang' : 'Selisih',
            'debit' => $balanced ? '0' : $selisih,
            'kredit' => '0',
        ]);

        return $rows;
    }
}
