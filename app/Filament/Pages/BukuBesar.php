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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BukuBesar extends Page implements HasSchemas, HasTable
{
    use HasPageShield;
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static \UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Buku Besar';

    protected static ?string $navigationLabel = 'Buku Besar';

    public ?string $activeTab = null;

    public ?Model $parentRecord = null;

    public function getTitle(): string|Htmlable
    {
        return 'Buku Besar';
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
                return $this->buildLedger($filters);
            })
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y'),
                TextColumn::make('nomor_bukti')
                    ->label('No. Bukti'),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(50),
                TextColumn::make('debit')
                    ->label('Debit')
                    ->money('IDR')
                    ->alignEnd(),
                TextColumn::make('kredit')
                    ->label('Kredit')
                    ->money('IDR')
                    ->alignEnd(),
                TextColumn::make('saldo')
                    ->label('Saldo')
                    ->money('IDR')
                    ->alignEnd()
                    ->weight('bold'),
            ])
            ->filters([
                SelectFilter::make('akun_id')
                    ->label('Akun')
                    ->options(Akun::query()->orderBy('kode')->pluck('nama', 'id'))
                    ->searchable(),
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
            ->emptyStateDescription('Silakan pilih akun dan rentang tanggal untuk melihat buku besar.')
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
        $akunFilter = $this->getTableFilterState('akun_id') ?? [];
        $tanggalFilter = $this->getTableFilterState('tanggal') ?? [];

        $akunId = $akunFilter['value'] ?? null;
        $tanggalMulai = $tanggalFilter['tanggal_mulai'] ?? null;
        $tanggalAkhir = $tanggalFilter['tanggal_akhir'] ?? null;

        $rows = $this->buildLedger([
            'akun_id' => $akunFilter,
            'tanggal' => $tanggalFilter,
        ]);

        $akun = $akunId ? Akun::withTrashed()->find($akunId) : null;

        $baris = $rows
            ->map(fn (array $row): array => [
                $row['tanggal'] ? Carbon::parse($row['tanggal'])->format('d/m/Y') : '-',
                $row['nomor_bukti'],
                $row['keterangan'],
                number_format((float) $row['debit'], 0, ',', '.'),
                number_format((float) $row['kredit'], 0, ',', '.'),
                number_format((float) $row['saldo'], 0, ',', '.'),
            ])
            ->values()
            ->all();

        $judul = $akun
            ? 'BUKU BESAR — '.$akun->kode.' '.$akun->nama
            : 'BUKU BESAR';

        $pdf = LaporanPdfService::make()
            ->judul($judul)
            ->periode($this->labelPeriode($tanggalMulai, $tanggalAkhir))
            ->kolom([
                'Tanggal',
                'No. Bukti',
                'Keterangan',
                ['Debit (Rp)', 'right'],
                ['Kredit (Rp)', 'right'],
                ['Saldo (Rp)', 'right'],
            ])
            ->baris($baris)
            ->landscape()
            ->render();

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            LaporanPdfService::make()->namaFile(
                'buku-besar-'.($akun?->kode ?? 'akun').'-'.($tanggalAkhir ?? now()->toDateString())
            ),
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
     * Build a real ledger for a single account using snapshot semantics.
     *
     * The opening balance is the saldo awal snapshot (latest saldo_awals row
     * with tanggal <= tanggal mulai, NOT a sum across tahun ajaran) plus the
     * journal movement from the snapshot date up to — but excluding — tanggal
     * mulai (jurnal >= snapshot.tanggal AND < tanggal mulai). Period movement
     * is jurnal in [tanggal mulai, tanggal akhir]. With this, a saldo awal dated
     * exactly on tanggal mulai lands in the opening balance (not lost, not
     * double-counted) and reconciles with Neraca. Honors posisi_normal and runs
     * a saldo per entry; soft-deleted rows are excluded.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function buildLedger(array $filters): Collection
    {
        $akunId = $filters['akun_id']['value'] ?? null;
        $tanggalMulai = $filters['tanggal']['tanggal_mulai'] ?? null;
        $tanggalAkhir = $filters['tanggal']['tanggal_akhir'] ?? null;

        if (! $akunId) {
            return collect();
        }

        $akun = Akun::withTrashed()->find($akunId);

        if (! $akun) {
            return collect();
        }

        $isDebitNormal = $akun->posisi_normal === 'debit';

        $saldo = $tanggalMulai
            ? (app(FinancialService::class)
                ->saldoAwalPeriodePerAkun([$akun->id], $tanggalMulai)[$akun->id] ?? '0.00')
            : '0.00';

        $rows = collect();
        $rows->push([
            'tanggal' => $tanggalMulai ? Carbon::parse($tanggalMulai) : null,
            'nomor_bukti' => '-',
            'keterangan' => 'Saldo Awal',
            'debit' => '0',
            'kredit' => '0',
            'saldo' => $saldo,
        ]);

        $entries = JurnalUmum::query()
            ->where('akun_id', $akunId)
            ->when($tanggalMulai, fn ($q) => $q->where('tanggal', '>=', $tanggalMulai))
            ->when($tanggalAkhir, fn ($q) => $q->where('tanggal', '<=', $tanggalAkhir))
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        foreach ($entries as $entry) {
            $debit = (string) $entry->debit;
            $kredit = (string) $entry->kredit;
            $net = $isDebitNormal
                ? bcsub($debit, $kredit, 2)
                : bcsub($kredit, $debit, 2);
            $saldo = bcadd($saldo, $net, 2);

            $rows->push([
                'tanggal' => $entry->tanggal,
                'nomor_bukti' => $entry->nomor_bukti,
                'keterangan' => $entry->keterangan,
                'debit' => $debit,
                'kredit' => $kredit,
                'saldo' => $saldo,
            ]);
        }

        $rows->push([
            'tanggal' => $tanggalAkhir ? Carbon::parse($tanggalAkhir) : null,
            'nomor_bukti' => '-',
            'keterangan' => 'Saldo Akhir',
            'debit' => '0',
            'kredit' => '0',
            'saldo' => $saldo,
        ]);

        return $rows->values();
    }
}
