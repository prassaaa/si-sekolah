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
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * F14 — Kas Kecil (imprest).
 *
 * Laporan transaksi kas pada akun Kas Kecil (kode 1-1005): gabungan KasMasuk +
 * KasKeluar yang kas_akun_id-nya = akun Kas Kecil, dengan saldo berjalan, filter
 * bulan, dan cetak PDF. Tanpa logika transfer khusus — murni laporan + saldo.
 */
class KasKecil extends Page implements HasSchemas, HasTable
{
    use HasPageShield;
    use InteractsWithSchemas, InteractsWithTable;

    protected const KODE_AKUN = '1-1005';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static \UnitEnum|string|null $navigationGroup = 'Kas & Bank';

    protected static ?int $navigationSort = 13;

    protected static ?string $title = 'Kas Kecil';

    protected static ?string $navigationLabel = 'Kas Kecil';

    /**
     * Ringkasan periode terpilih (saldo awal, total masuk/keluar, saldo akhir),
     * diisi oleh buildData() agar tabel layar dan ekspor PDF konsisten.
     *
     * @var array{
     *     saldo_awal: string,
     *     total_masuk: string,
     *     total_keluar: string,
     *     saldo_akhir: string
     * }|array{}
     */
    public array $ringkasan = [];

    public function getTitle(): string|Htmlable
    {
        return 'Kas Kecil';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    /**
     * Akun Kas Kecil (kode 1-1005), atau null jika belum di-seed.
     */
    public static function akunKasKecil(): ?Akun
    {
        return Akun::query()->where('kode', self::KODE_AKUN)->first();
    }

    /**
     * Bangun baris transaksi Kas Kecil: gabungkan kas_masuks (masuk) dan
     * kas_keluars (keluar) yang kas_akun_id = akun Kas Kecil, urutkan tanggal lalu
     * id, hitung saldo berjalan. Saldo awal = saldo akun Kas Kecil SEBELUM
     * tanggal_mulai (eksklusif) via FinancialService::saldoAwalPeriodePerAkun.
     *
     * @return Collection<int, array{
     *     tanggal: string,
     *     nomor_bukti: string,
     *     uraian: string,
     *     masuk: string,
     *     keluar: string,
     *     saldo: string,
     * }>
     */
    public function buildData(?string $bulan): Collection
    {
        $akun = static::akunKasKecil();

        if (! $bulan || ! $akun) {
            $this->ringkasan = [];

            return collect();
        }

        $periode = Carbon::parse($bulan.'-01');
        $tanggalMulai = $periode->copy()->startOfMonth()->toDateString();
        $tanggalAkhir = $periode->copy()->endOfMonth()->toDateString();

        $saldoAwal = app(FinancialService::class)
            ->saldoAwalPeriodePerAkun([$akun->id], $tanggalMulai)[$akun->id] ?? '0';

        $baris = collect();

        $masuk = KasMasuk::query()
            ->where('kas_akun_id', $akun->id)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        foreach ($masuk as $item) {
            $baris->push([
                'tanggal' => $item->tanggal->format('Y-m-d'),
                'nomor_bukti' => $item->nomor_bukti,
                'uraian' => $item->sumber ?? $item->keterangan ?? '-',
                'masuk' => (string) $item->nominal,
                'keluar' => '0',
            ]);
        }

        $keluar = KasKeluar::query()
            ->where('kas_akun_id', $akun->id)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        foreach ($keluar as $item) {
            $baris->push([
                'tanggal' => $item->tanggal->format('Y-m-d'),
                'nomor_bukti' => $item->nomor_bukti,
                'uraian' => $item->penerima ?? $item->keterangan ?? '-',
                'masuk' => '0',
                'keluar' => (string) $item->nominal,
            ]);
        }

        $baris = $baris->sortBy([['tanggal', 'asc'], ['nomor_bukti', 'asc']])->values();

        $saldo = $saldoAwal;
        $totalMasuk = '0';
        $totalKeluar = '0';

        $baris = $baris->map(function (array $row) use (&$saldo, &$totalMasuk, &$totalKeluar): array {
            $totalMasuk = bcadd($totalMasuk, $row['masuk'], 2);
            $totalKeluar = bcadd($totalKeluar, $row['keluar'], 2);
            $saldo = bcadd(bcsub($saldo, $row['keluar'], 2), $row['masuk'], 2);

            return array_merge($row, ['saldo' => $saldo]);
        });

        $this->ringkasan = [
            'saldo_awal' => $saldoAwal,
            'total_masuk' => $totalMasuk,
            'total_keluar' => $totalKeluar,
            'saldo_akhir' => $saldo,
        ];

        return $baris;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (array $filters): Collection {
                return $this->buildData($filters['periode']['bulan'] ?? null);
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

                TextColumn::make('masuk')
                    ->label('Masuk')
                    ->formatStateUsing(fn (string $state): string => (float) $state > 0
                        ? 'Rp '.number_format((float) $state, 0, ',', '.')
                        : '-'
                    )
                    ->alignEnd(),

                TextColumn::make('keluar')
                    ->label('Keluar')
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
            ])
            ->deferFilters(false)
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada transaksi')
            ->emptyStateDescription('Pilih bulan untuk menampilkan transaksi Kas Kecil.')
            ->emptyStateIcon('heroicon-o-banknotes');
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
        $bulan = $this->getTableFilterState('periode')['bulan'] ?? null;

        $rows = $this->buildData($bulan);
        $ringkasan = $this->ringkasan;

        $no = 1;
        $baris = $rows->map(fn (array $row): array => [
            (string) $no++,
            Carbon::parse($row['tanggal'])->format('d/m/Y'),
            $row['nomor_bukti'],
            $row['uraian'],
            (float) $row['masuk'] > 0 ? 'Rp '.number_format((float) $row['masuk'], 0, ',', '.') : '-',
            (float) $row['keluar'] > 0 ? 'Rp '.number_format((float) $row['keluar'], 0, ',', '.') : '-',
            'Rp '.number_format((float) $row['saldo'], 0, ',', '.'),
        ])->toArray();

        $rekap = [
            ['', '', '', 'Saldo Awal', '', '', 'Rp '.number_format((float) ($ringkasan['saldo_awal'] ?? 0), 0, ',', '.')],
            ['', '', '', 'Total Masuk', 'Rp '.number_format((float) ($ringkasan['total_masuk'] ?? 0), 0, ',', '.'), '', ''],
            ['', '', '', 'Total Keluar', '', 'Rp '.number_format((float) ($ringkasan['total_keluar'] ?? 0), 0, ',', '.'), ''],
            ['', '', '', 'Saldo Akhir', '', '', 'Rp '.number_format((float) ($ringkasan['saldo_akhir'] ?? 0), 0, ',', '.')],
        ];

        $pdf = LaporanPdfService::make()
            ->judul('LAPORAN KAS KECIL')
            ->periode($bulan ? 'Periode '.Carbon::parse($bulan.'-01')->translatedFormat('F Y') : null)
            ->kolom([
                'No',
                'Tanggal',
                'No. Bukti',
                'Uraian',
                ['Masuk', 'right'],
                ['Keluar', 'right'],
                ['Saldo', 'right'],
            ])
            ->baris($baris)
            ->ringkasan($rekap)
            ->landscape()
            ->render();

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            LaporanPdfService::make()->namaFile('kas-kecil-'.($bulan ?? now()->format('Y-m'))),
        );
    }
}
