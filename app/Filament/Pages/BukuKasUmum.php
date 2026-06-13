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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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

class BukuKasUmum extends Page implements HasSchemas, HasTable
{
    use HasPageShield;
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static \UnitEnum|string|null $navigationGroup = 'Kas & Bank';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Buku Kas Umum';

    protected static ?string $navigationLabel = 'Buku Kas Umum';

    /**
     * Ringkasan BKU periode terpilih (saldo awal, total penerimaan/pengeluaran,
     * saldo akhir). Diisi oleh buildData() agar tabel layar dan ekspor PDF
     * menggunakan data yang sama.
     *
     * @var array{
     *     saldo_awal: string,
     *     total_penerimaan: string,
     *     total_pengeluaran: string,
     *     saldo_akhir: string
     * }|array{}
     */
    public array $ringkasan = [];

    public function getTitle(): string|Htmlable
    {
        return 'Buku Kas Umum';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    /**
     * Id seluruh akun kas/bank untuk keperluan saldo awal periode.
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
     * Bangun baris BKU: gabungkan kas_masuks (penerimaan) dan kas_keluars
     * (pengeluaran), urutkan tanggal lalu id, hitung saldo berjalan kumulatif.
     *
     * Saldo awal = saldo seluruh akun kas/bank SEBELUM tanggal_mulai (eksklusif)
     * — konsisten dengan pola ArusKasBank / saldoAwalPeriodePerAkun.
     *
     * @return Collection<int, array{
     *     tanggal: string,
     *     nomor_bukti: string,
     *     uraian: string,
     *     penerimaan: string,
     *     pengeluaran: string,
     *     saldo: string,
     *     sumber_dana: string,
     *     akun_kas_id: int|null,
     * }>
     */
    public function buildData(
        ?string $bulan,
        ?string $sumberDana,
        ?int $kasAkunId,
    ): Collection {
        if (! $bulan) {
            $this->ringkasan = [];

            return collect();
        }

        $periode = Carbon::parse($bulan.'-01');
        $tanggalMulai = $periode->copy()->startOfMonth()->toDateString();
        $tanggalAkhir = $periode->copy()->endOfMonth()->toDateString();

        $kasAkunIds = $kasAkunId !== null ? [$kasAkunId] : $this->kasAkunIds();

        $saldoAwalPerAkun = $kasAkunIds === []
            ? []
            : app(FinancialService::class)->saldoAwalPeriodePerAkun($kasAkunIds, $tanggalMulai);

        $saldoAwal = array_reduce(
            $saldoAwalPerAkun,
            fn (string $carry, string|array $saldo): string => bcadd(
                $carry,
                is_array($saldo) ? (string) ($saldo['saldo'] ?? '0') : (string) $saldo,
                2,
            ),
            '0',
        );

        // Kumpulkan semua baris mentah sebelum dihitung saldo berjalan.
        $baris = collect();

        $queryMasuk = KasMasuk::query()
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir]);

        if ($sumberDana) {
            $queryMasuk->where('sumber_dana', $sumberDana);
        }

        if ($kasAkunId !== null) {
            $queryMasuk->where('kas_akun_id', $kasAkunId);
        }

        foreach ($queryMasuk->orderBy('tanggal')->orderBy('id')->get() as $item) {
            $baris->push([
                'tanggal' => $item->tanggal->format('Y-m-d'),
                'nomor_bukti' => $item->nomor_bukti,
                'uraian' => $item->sumber ?? $item->keterangan ?? '-',
                'penerimaan' => (string) $item->nominal,
                'pengeluaran' => '0',
                'sumber_dana' => $item->sumber_dana ?? 'lainnya',
                'akun_kas_id' => $item->kas_akun_id,
            ]);
        }

        $queryKeluar = KasKeluar::query()
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir]);

        if ($sumberDana) {
            $queryKeluar->where('sumber_dana', $sumberDana);
        }

        if ($kasAkunId !== null) {
            $queryKeluar->where('kas_akun_id', $kasAkunId);
        }

        foreach ($queryKeluar->orderBy('tanggal')->orderBy('id')->get() as $item) {
            $baris->push([
                'tanggal' => $item->tanggal->format('Y-m-d'),
                'nomor_bukti' => $item->nomor_bukti,
                'uraian' => $item->penerima ?? $item->keterangan ?? '-',
                'penerimaan' => '0',
                'pengeluaran' => (string) $item->nominal,
                'sumber_dana' => $item->sumber_dana ?? 'lainnya',
                'akun_kas_id' => $item->kas_akun_id,
            ]);
        }

        // Urutkan tanggal, kemudian nomor bukti (agar deterministik).
        $baris = $baris->sortBy([['tanggal', 'asc'], ['nomor_bukti', 'asc']])->values();

        // Hitung saldo berjalan kumulatif.
        $saldo = $saldoAwal;
        $totalPenerimaan = '0';
        $totalPengeluaran = '0';

        $baris = $baris->map(function (array $row) use (&$saldo, &$totalPenerimaan, &$totalPengeluaran): array {
            $totalPenerimaan = bcadd($totalPenerimaan, $row['penerimaan'], 2);
            $totalPengeluaran = bcadd($totalPengeluaran, $row['pengeluaran'], 2);
            $saldo = bcadd(bcsub($saldo, $row['pengeluaran'], 2), $row['penerimaan'], 2);

            return array_merge($row, ['saldo' => $saldo]);
        });

        $this->ringkasan = [
            'saldo_awal' => $saldoAwal,
            'total_penerimaan' => $totalPenerimaan,
            'total_pengeluaran' => $totalPengeluaran,
            'saldo_akhir' => $saldo,
        ];

        return $baris;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (array $filters): Collection {
                return $this->buildData(
                    $filters['periode']['bulan'] ?? null,
                    $filters['sumber_dana']['value'] ?? null,
                    isset($filters['akun_kas']['akun_kas_id']) && $filters['akun_kas']['akun_kas_id'] !== ''
                        ? (int) $filters['akun_kas']['akun_kas_id']
                        : null,
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
                TextColumn::make('uraian')
                    ->label('Uraian')
                    ->limit(50),
                TextColumn::make('penerimaan')
                    ->label('Penerimaan')
                    ->formatStateUsing(fn (string $state): string => (float) $state > 0
                        ? 'Rp '.number_format((float) $state, 0, ',', '.')
                        : '-'
                    )
                    ->alignEnd(),
                TextColumn::make('pengeluaran')
                    ->label('Pengeluaran')
                    ->formatStateUsing(fn (string $state): string => (float) $state > 0
                        ? 'Rp '.number_format((float) $state, 0, ',', '.')
                        : '-'
                    )
                    ->alignEnd(),
                TextColumn::make('saldo')
                    ->label('Saldo')
                    ->formatStateUsing(fn (string $state): string => 'Rp '.number_format((float) $state, 0, ',', '.'))
                    ->alignEnd()
                    ->weight('bold'),
            ])
            ->filters([
                Filter::make('periode')
                    ->form([
                        TextInput::make('bulan')
                            ->label('Bulan')
                            ->type('month')
                            ->default(now()->format('Y-m')),
                    ])
                    ->indicateUsing(function (array $data): array {
                        if (! ($data['bulan'] ?? null)) {
                            return [];
                        }

                        return ['Bulan: '.Carbon::parse($data['bulan'].'-01')->translatedFormat('F Y')];
                    }),
                SelectFilter::make('sumber_dana')
                    ->label('Sumber Dana')
                    ->options([
                        'bos' => 'BOS',
                        'komite' => 'Komite',
                        'yayasan' => 'Yayasan',
                        'lainnya' => 'Lainnya',
                    ])
                    ->placeholder('Semua Sumber Dana'),
                Filter::make('akun_kas')
                    ->form([
                        Select::make('akun_kas_id')
                            ->label('Akun Kas/Bank')
                            ->options(
                                Akun::query()
                                    ->where('tipe', 'aset')
                                    ->where('kategori', 'lancar')
                                    ->where(function ($q): void {
                                        $q->where('nama', 'like', '%Kas%')
                                            ->orWhere('nama', 'like', '%Bank%');
                                    })
                                    ->orderBy('kode')
                                    ->pluck('nama', 'id')
                            )
                            ->placeholder('Semua Akun Kas/Bank')
                            ->searchable(),
                    ])
                    ->indicateUsing(function (array $data): array {
                        if (! ($data['akun_kas_id'] ?? null)) {
                            return [];
                        }
                        $nama = Akun::find($data['akun_kas_id'])?->nama;

                        return $nama ? ['Akun Kas: '.$nama] : [];
                    }),
            ])
            ->deferFilters(false)
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada data')
            ->emptyStateDescription('Pilih bulan untuk menampilkan Buku Kas Umum.')
            ->emptyStateIcon('heroicon-o-book-open');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cetakPdf')
                ->label('Cetak BKU')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(fn (): StreamedResponse => $this->cetakPdf()),
        ];
    }

    private function cetakPdf(): StreamedResponse
    {
        $filterPeriode = $this->getTableFilterState('periode') ?? [];
        $bulan = $filterPeriode['bulan'] ?? null;
        $sumberDana = $this->getTableFilterState('sumber_dana')['value'] ?? null;
        $filterAkunKas = $this->getTableFilterState('akun_kas') ?? [];
        $kasAkunId = isset($filterAkunKas['akun_kas_id']) && $filterAkunKas['akun_kas_id'] !== ''
            ? (int) $filterAkunKas['akun_kas_id']
            : null;

        $rows = $this->buildData($bulan, $sumberDana, $kasAkunId);
        $ringkasan = $this->ringkasan;

        $no = 1;
        $baris = $rows->map(function (array $row) use (&$no): array {
            return [
                (string) $no++,
                Carbon::parse($row['tanggal'])->format('d/m/Y'),
                $row['nomor_bukti'],
                $row['uraian'],
                (float) $row['penerimaan'] > 0
                    ? 'Rp '.number_format((float) $row['penerimaan'], 0, ',', '.')
                    : '-',
                (float) $row['pengeluaran'] > 0
                    ? 'Rp '.number_format((float) $row['pengeluaran'], 0, ',', '.')
                    : '-',
                'Rp '.number_format((float) $row['saldo'], 0, ',', '.'),
            ];
        })->toArray();

        $rekap = [
            ['', '', '', 'Saldo Awal', '', '', 'Rp '.number_format((float) ($ringkasan['saldo_awal'] ?? 0), 0, ',', '.')],
            ['', '', '', 'Total Penerimaan', 'Rp '.number_format((float) ($ringkasan['total_penerimaan'] ?? 0), 0, ',', '.'), '', ''],
            ['', '', '', 'Total Pengeluaran', '', 'Rp '.number_format((float) ($ringkasan['total_pengeluaran'] ?? 0), 0, ',', '.'), ''],
            ['', '', '', 'Saldo Akhir', '', '', 'Rp '.number_format((float) ($ringkasan['saldo_akhir'] ?? 0), 0, ',', '.')],
        ];

        $sumberLabel = match ($sumberDana) {
            'bos' => 'BOS',
            'komite' => 'Komite',
            'yayasan' => 'Yayasan',
            'lainnya' => 'Lainnya',
            default => 'Semua Sumber Dana',
        };

        $judulLengkap = 'BUKU KAS UMUM — '.$sumberLabel;

        $pdf = LaporanPdfService::make()
            ->judul($judulLengkap)
            ->periode($this->periodeLabel($bulan))
            ->kolom([
                'No',
                'Tanggal',
                'No. Bukti',
                'Uraian',
                ['Penerimaan', 'right'],
                ['Pengeluaran', 'right'],
                ['Saldo', 'right'],
            ])
            ->baris($baris)
            ->ringkasan($rekap)
            ->landscape()
            ->render();

        $namaFile = LaporanPdfService::make()
            ->namaFile('bku-'.($sumberDana ?? 'semua').'-'.($bulan ?? now()->format('Y-m')));

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $namaFile,
        );
    }

    private function periodeLabel(?string $bulan): ?string
    {
        if (! $bulan) {
            return null;
        }

        return 'Periode '.Carbon::parse($bulan.'-01')->translatedFormat('F Y');
    }
}
