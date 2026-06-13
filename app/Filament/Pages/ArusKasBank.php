<?php

namespace App\Filament\Pages;

use App\Models\Akun;
use App\Models\KasKeluar;
use App\Models\KasMasuk;
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

class ArusKasBank extends Page implements HasSchemas, HasTable
{
    use HasPageShield;
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static \UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Arus Kas/Bank';

    protected static ?string $navigationLabel = 'Arus Kas/Bank';

    /**
     * Ringkasan arus kas periode terpilih (saldo awal/akhir + total),
     * diisi oleh buildData() agar tabel dan ekspor PDF konsisten.
     *
     * @var array{
     *     saldo_awal: string,
     *     total_penerimaan: string,
     *     total_pengeluaran: string,
     *     arus_kas_bersih: string,
     *     saldo_akhir: string
     * }|array{}
     */
    public array $ringkasan = [];

    public function getTitle(): string|Htmlable
    {
        return 'Arus Kas/Bank';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    /**
     * Id seluruh akun kas/bank (tipe aset, kategori lancar, nama mengandung
     * "Kas" atau "Bank"). Inilah akun yang saldonya dilaporkan dalam laporan
     * arus kas.
     *
     * @return array<int>
     */
    private function kasAkunIds(): array
    {
        return Akun::query()
            ->where('tipe', 'aset')
            ->where('kategori', 'lancar')
            ->where(function ($query): void {
                $query->where('nama', 'like', '%Kas%')
                    ->orWhere('nama', 'like', '%Bank%');
            })
            ->orderBy('kode')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * Klasifikasikan satu transaksi kas ke aktivitas arus kas berdasarkan tipe
     * akun lawan: pendapatan/beban => Operasi, aset (tetap) => Investasi,
     * ekuitas/liabilitas => Pendanaan.
     */
    private function klasifikasiAktivitas(?Akun $akunLawan): string
    {
        return match ($akunLawan?->tipe) {
            'pendapatan', 'beban' => 'Operasi',
            'aset' => 'Investasi',
            'ekuitas', 'liabilitas' => 'Pendanaan',
            default => 'Operasi',
        };
    }

    /**
     * Hitung saldo kas awal periode, baris transaksi (dengan klasifikasi
     * aktivitas), total penerimaan/pengeluaran, dan saldo kas akhir untuk
     * rentang tanggal terpilih. Dipakai bersama oleh tabel layar dan PDF.
     *
     * Saldo kas awal diambil dari FinancialService::saldoAwalPeriodePerAkun
     * (saldo seluruh akun kas/bank tepat SEBELUM tanggal mulai), sehingga saldo
     * akhir = saldo awal + penerimaan - pengeluaran.
     *
     * @return Collection<int, array{
     *     tanggal: string,
     *     nomor_bukti: string,
     *     akun: string,
     *     aktivitas: string,
     *     keterangan: string,
     *     jenis: string,
     *     nominal: string
     * }>
     */
    public function buildData(?string $tanggalMulai, ?string $tanggalAkhir, ?string $jenis = null): Collection
    {
        if (! $tanggalMulai || ! $tanggalAkhir) {
            $this->ringkasan = [];

            return collect();
        }

        $kasAkunIds = $this->kasAkunIds();

        $saldoAwalPerAkun = $kasAkunIds === []
            ? []
            : app(FinancialService::class)->saldoAwalPeriodePerAkun($kasAkunIds, $tanggalMulai);

        $saldoAwal = array_reduce(
            $saldoAwalPerAkun,
            fn (string $carry, string $saldo): string => bcadd($carry, $saldo, 2),
            '0',
        );

        $data = collect();
        $totalPenerimaan = '0';
        $totalPengeluaran = '0';

        if (! $jenis || $jenis === 'masuk') {
            $kasMasuk = KasMasuk::query()
                ->with('akun')
                ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
                ->orderBy('tanggal')
                ->get();

            foreach ($kasMasuk as $item) {
                $totalPenerimaan = bcadd($totalPenerimaan, (string) $item->nominal, 2);

                $data->push([
                    'tanggal' => $item->tanggal->format('Y-m-d'),
                    'nomor_bukti' => $item->nomor_bukti,
                    'akun' => $item->akun?->nama ?? '-',
                    'aktivitas' => $this->klasifikasiAktivitas($item->akun),
                    'keterangan' => $item->sumber ?? '-',
                    'jenis' => 'Masuk',
                    'nominal' => (string) $item->nominal,
                ]);
            }
        }

        if (! $jenis || $jenis === 'keluar') {
            $kasKeluar = KasKeluar::query()
                ->with('akun')
                ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
                ->orderBy('tanggal')
                ->get();

            foreach ($kasKeluar as $item) {
                $totalPengeluaran = bcadd($totalPengeluaran, (string) $item->nominal, 2);

                $data->push([
                    'tanggal' => $item->tanggal->format('Y-m-d'),
                    'nomor_bukti' => $item->nomor_bukti,
                    'akun' => $item->akun?->nama ?? '-',
                    'aktivitas' => $this->klasifikasiAktivitas($item->akun),
                    'keterangan' => $item->penerima ?? '-',
                    'jenis' => 'Keluar',
                    'nominal' => (string) $item->nominal,
                ]);
            }
        }

        $arusKasBersih = bcsub($totalPenerimaan, $totalPengeluaran, 2);

        $this->ringkasan = [
            'saldo_awal' => $saldoAwal,
            'total_penerimaan' => $totalPenerimaan,
            'total_pengeluaran' => $totalPengeluaran,
            'arus_kas_bersih' => $arusKasBersih,
            'saldo_akhir' => bcadd($saldoAwal, $arusKasBersih, 2),
        ];

        return $data->sortBy('tanggal')->values();
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (array $filters): Collection {
                return $this->buildData(
                    $filters['tanggal']['tanggal_mulai'] ?? null,
                    $filters['tanggal']['tanggal_akhir'] ?? null,
                    $filters['jenis']['value'] ?? null,
                );
            })
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('nomor_bukti')
                    ->label('No. Bukti')
                    ->searchable(),
                TextColumn::make('akun')
                    ->label('Akun'),
                TextColumn::make('aktivitas')
                    ->label('Aktivitas')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Operasi' => 'success',
                        'Investasi' => 'warning',
                        'Pendanaan' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(40),
                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Masuk' => 'success',
                        'Keluar' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->alignEnd()
                    ->weight('bold'),
            ])
            ->filters([
                SelectFilter::make('jenis')
                    ->label('Jenis')
                    ->options([
                        'masuk' => 'Kas Masuk',
                        'keluar' => 'Kas Keluar',
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
            ->emptyStateDescription('Silakan pilih rentang tanggal untuk melihat arus kas.')
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

    private function cetakPdf(): StreamedResponse
    {
        $filters = $this->getTableFilterState('tanggal') ?? [];
        $jenis = $this->getTableFilterState('jenis')['value'] ?? null;

        $tanggalMulai = $filters['tanggal_mulai'] ?? null;
        $tanggalAkhir = $filters['tanggal_akhir'] ?? null;

        $rows = $this->buildData($tanggalMulai, $tanggalAkhir, $jenis);

        $no = 1;
        $baris = $rows->map(function (array $row) use (&$no): array {
            return [
                (string) $no++,
                Carbon::parse($row['tanggal'])->format('d/m/Y'),
                $row['nomor_bukti'],
                $row['akun'],
                $row['aktivitas'],
                $row['keterangan'],
                $row['jenis'],
                'Rp '.number_format((float) $row['nominal'], 0, ',', '.'),
            ];
        })->toArray();

        $ringkasan = $this->ringkasan;

        $rekap = [
            ['', '', '', '', '', '', 'Saldo Kas Awal', 'Rp '.number_format((float) ($ringkasan['saldo_awal'] ?? 0), 0, ',', '.')],
            ['', '', '', '', '', '', 'Total Penerimaan', 'Rp '.number_format((float) ($ringkasan['total_penerimaan'] ?? 0), 0, ',', '.')],
            ['', '', '', '', '', '', 'Total Pengeluaran', 'Rp '.number_format((float) ($ringkasan['total_pengeluaran'] ?? 0), 0, ',', '.')],
            ['', '', '', '', '', '', 'Saldo Kas Akhir', 'Rp '.number_format((float) ($ringkasan['saldo_akhir'] ?? 0), 0, ',', '.')],
        ];

        $pdf = LaporanPdfService::make()
            ->judul('LAPORAN ARUS KAS')
            ->periode($this->periodeLabel($tanggalMulai, $tanggalAkhir))
            ->kolom([
                'No',
                'Tanggal',
                'No. Bukti',
                'Akun',
                'Aktivitas',
                'Keterangan',
                'Jenis',
                ['Nominal', 'right'],
            ])
            ->baris($baris)
            ->ringkasan($rekap)
            ->landscape()
            ->render();

        $namaFile = LaporanPdfService::make()
            ->namaFile('laporan-arus-kas-'.now()->format('Ymd'));

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $namaFile,
        );
    }

    private function periodeLabel(?string $tanggalMulai, ?string $tanggalAkhir): ?string
    {
        if (! $tanggalMulai || ! $tanggalAkhir) {
            return null;
        }

        return 'Periode '.Carbon::parse($tanggalMulai)->translatedFormat('d F Y')
            .' s/d '.Carbon::parse($tanggalAkhir)->translatedFormat('d F Y');
    }
}
