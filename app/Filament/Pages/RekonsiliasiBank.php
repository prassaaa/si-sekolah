<?php

namespace App\Filament\Pages;

use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\MutasiBank;
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
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * F9 — Rekonsiliasi Bank.
 *
 * Membandingkan saldo buku akun bank (dari ledger via FinancialService) dengan
 * saldo rekening koran (dari mutasi yang diinput), menampilkan item belum cocok
 * (outstanding) dari kedua sisi, dan selisihnya. Dapat dicetak PDF.
 */
class RekonsiliasiBank extends Page implements HasSchemas, HasTable
{
    use HasPageShield;
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static \UnitEnum|string|null $navigationGroup = 'Kas & Bank';

    protected static ?int $navigationSort = 60;

    protected static ?string $title = 'Rekonsiliasi Bank';

    protected static ?string $navigationLabel = 'Rekonsiliasi Bank';

    /**
     * Ringkasan rekonsiliasi periode terpilih, diisi oleh buildData() agar tabel
     * layar dan ekspor PDF memakai data yang sama.
     *
     * @var array{
     *     saldo_buku: string,
     *     saldo_koran: string,
     *     selisih: string,
     *     outstanding_koran: string,
     *     outstanding_jurnal: string,
     * }|array{}
     */
    public array $ringkasan = [];

    public function getTitle(): string|Htmlable
    {
        return 'Rekonsiliasi Bank';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    /**
     * Pilihan akun bank (akun aset lancar yang namanya mengandung "Bank").
     *
     * @return array<int, string>
     */
    public static function bankAkunOptions(): array
    {
        return Akun::query()
            ->where('tipe', 'aset')
            ->where('kategori', 'lancar')
            ->where('nama', 'like', '%Bank%')
            ->orderBy('kode')
            ->get()
            ->mapWithKeys(fn (Akun $akun): array => [$akun->id => "{$akun->kode} - {$akun->nama}"])
            ->all();
    }

    /**
     * Bangun baris rekonsiliasi untuk satu akun bank pada satu bulan.
     *
     * Saldo buku: FinancialService::saldoPerAkun([akun], akhir bulan) — snapshot
     *   saldo awal + pergerakan jurnal s/d akhir bulan, arah posisi_normal.
     * Saldo rekening koran: bila baris mutasi terakhir (s/d akhir bulan)
     *   mencantumkan kolom `saldo`, dipakai langsung; jika tidak, dihitung sebagai
     *   kumulatif (debit - kredit) seluruh mutasi s/d akhir bulan (akun bank
     *   berposisi debit, sehingga debit menambah & kredit mengurangi saldo).
     * Outstanding sisi koran: mutasi belum cocok (is_matched = false) s/d akhir
     *   bulan. Outstanding sisi buku: jurnal pada akun bank s/d akhir bulan yang
     *   id-nya tidak tertaut oleh mutasi yang sudah dicocokkan (jurnal_umum_id).
     * Selisih = saldo buku - saldo koran.
     *
     * @return Collection<int, array{kategori: string, keterangan: string, tanggal: string, nilai: string}>
     */
    public function buildData(?int $akunId, ?string $bulan): Collection
    {
        if (! $akunId || ! $bulan) {
            $this->ringkasan = [];

            return collect();
        }

        $periode = Carbon::parse($bulan.'-01');
        $tanggalAkhir = $periode->copy()->endOfMonth()->toDateString();

        $saldoBuku = app(FinancialService::class)
            ->saldoPerAkun([$akunId], $tanggalAkhir)[$akunId] ?? '0';

        $mutasiSampaiAkhir = MutasiBank::query()
            ->where('akun_id', $akunId)
            ->whereDate('tanggal', '<=', $tanggalAkhir)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        $saldoKoran = $this->hitungSaldoKoran($mutasiSampaiAkhir);

        $outstandingKoran = $mutasiSampaiAkhir->where('is_matched', false);

        $jurnalTertaut = MutasiBank::query()
            ->where('akun_id', $akunId)
            ->where('is_matched', true)
            ->whereNotNull('jurnal_umum_id')
            ->pluck('jurnal_umum_id')
            ->all();

        $outstandingJurnal = JurnalUmum::query()
            ->where('akun_id', $akunId)
            ->whereDate('tanggal', '<=', $tanggalAkhir)
            ->when($jurnalTertaut !== [], fn ($q) => $q->whereNotIn('id', $jurnalTertaut))
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        $rows = collect();

        $totalOutstandingKoran = '0';
        foreach ($outstandingKoran as $mutasi) {
            $nilai = bcsub((string) $mutasi->debit, (string) $mutasi->kredit, 2);
            $totalOutstandingKoran = bcadd($totalOutstandingKoran, $nilai, 2);

            $rows->push([
                'kategori' => 'Outstanding Rekening Koran',
                'keterangan' => $mutasi->keterangan ?? '-',
                'tanggal' => $mutasi->tanggal->format('Y-m-d'),
                'nilai' => $nilai,
            ]);
        }

        $totalOutstandingJurnal = '0';
        foreach ($outstandingJurnal as $jurnal) {
            $nilai = bcsub((string) $jurnal->debit, (string) $jurnal->kredit, 2);
            $totalOutstandingJurnal = bcadd($totalOutstandingJurnal, $nilai, 2);

            $rows->push([
                'kategori' => 'Outstanding Jurnal (Buku)',
                'keterangan' => $jurnal->nomor_bukti.' — '.($jurnal->keterangan ?? '-'),
                'tanggal' => $jurnal->tanggal->format('Y-m-d'),
                'nilai' => $nilai,
            ]);
        }

        $selisih = bcsub($saldoBuku, $saldoKoran, 2);

        $this->ringkasan = [
            'saldo_buku' => $saldoBuku,
            'saldo_koran' => $saldoKoran,
            'selisih' => $selisih,
            'outstanding_koran' => $totalOutstandingKoran,
            'outstanding_jurnal' => $totalOutstandingJurnal,
        ];

        return $rows->values();
    }

    /**
     * Saldo rekening koran dari koleksi mutasi (sudah terurut tanggal lalu id).
     *
     * Jika baris terakhir mencantumkan `saldo`, nilai itu dipakai apa adanya;
     * jika tidak, saldo dihitung kumulatif debit - kredit (akun bank debit-normal).
     *
     * @param  Collection<int, MutasiBank>  $mutasi
     */
    private function hitungSaldoKoran(Collection $mutasi): string
    {
        if ($mutasi->isEmpty()) {
            return '0';
        }

        $terakhir = $mutasi->last();

        if ($terakhir->saldo !== null) {
            return (string) $terakhir->saldo;
        }

        $saldo = '0';
        foreach ($mutasi as $baris) {
            $saldo = bcadd($saldo, bcsub((string) $baris->debit, (string) $baris->kredit, 2), 2);
        }

        return $saldo;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (array $filters): Collection {
                $akunId = isset($filters['akun']['akun_id']) && $filters['akun']['akun_id'] !== ''
                    ? (int) $filters['akun']['akun_id']
                    : null;

                return $this->buildData($akunId, $filters['periode']['bulan'] ?? null);
            })
            ->columns([
                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Outstanding Rekening Koran' => 'warning',
                        'Outstanding Jurnal (Buku)' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y'),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(50),

                TextColumn::make('nilai')
                    ->label('Nilai')
                    ->money('IDR')
                    ->alignEnd()
                    ->weight('bold'),
            ])
            ->filters([
                Filter::make('akun')
                    ->form([
                        Select::make('akun_id')
                            ->label('Akun Bank')
                            ->options(fn (): array => static::bankAkunOptions())
                            ->searchable(),
                    ])
                    ->indicateUsing(function (array $data): array {
                        if (! ($data['akun_id'] ?? null)) {
                            return [];
                        }
                        $nama = Akun::find($data['akun_id'])?->nama;

                        return $nama ? ['Akun: '.$nama] : [];
                    }),

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
            ->emptyStateHeading('Tidak ada item outstanding')
            ->emptyStateDescription('Pilih akun bank dan bulan untuk menampilkan rekonsiliasi.')
            ->emptyStateIcon('heroicon-o-arrows-right-left');
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
        $filterAkun = $this->getTableFilterState('akun') ?? [];
        $akunId = isset($filterAkun['akun_id']) && $filterAkun['akun_id'] !== ''
            ? (int) $filterAkun['akun_id']
            : null;
        $bulan = $this->getTableFilterState('periode')['bulan'] ?? null;

        $rows = $this->buildData($akunId, $bulan);
        $ringkasan = $this->ringkasan;

        $baris = $rows->map(fn (array $row): array => [
            $row['kategori'],
            Carbon::parse($row['tanggal'])->format('d/m/Y'),
            $row['keterangan'],
            number_format((float) $row['nilai'], 0, ',', '.'),
        ])->all();

        $rekap = [
            ['Saldo Buku (Ledger)', '', '', number_format((float) ($ringkasan['saldo_buku'] ?? 0), 0, ',', '.')],
            ['Saldo Rekening Koran', '', '', number_format((float) ($ringkasan['saldo_koran'] ?? 0), 0, ',', '.')],
            ['Selisih', '', '', number_format((float) ($ringkasan['selisih'] ?? 0), 0, ',', '.')],
        ];

        $akunNama = $akunId ? (Akun::find($akunId)?->nama ?? '-') : '-';

        $pdf = LaporanPdfService::make()
            ->judul('REKONSILIASI BANK — '.$akunNama)
            ->periode($bulan ? Carbon::parse($bulan.'-01')->translatedFormat('F Y') : 'Periode')
            ->kolom(['Kategori', 'Tanggal', 'Keterangan', ['Nilai (Rp)', 'right']])
            ->baris($baris)
            ->ringkasan($rekap)
            ->landscape()
            ->render();

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            LaporanPdfService::make()->namaFile('rekonsiliasi-bank-'.($akunId ?? '0').'-'.($bulan ?? now()->format('Y-m'))),
        );
    }
}
