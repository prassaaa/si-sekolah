<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanDebitKreditStats;
use App\Models\KasKeluar;
use App\Models\KasMasuk;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanDebitKredit extends Page implements HasSchemas, HasTable
{
    use HasPageShield;
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static \UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 100;

    protected static ?string $title = 'Laporan Debit Kredit';

    protected static ?string $slug = 'laporan/debit-kredit';

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Debit & Kredit (Kas)';
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
            ->records(function (array $filters, int $page, int $recordsPerPage): LengthAwarePaginator {
                $tanggalMulai = $filters['tanggal']['tanggal_mulai'] ?? null;
                $tanggalSelesai = $filters['tanggal']['tanggal_selesai'] ?? null;
                $jenis = $filters['jenis']['value'] ?? null;

                // Total dihitung via SQL SUM (independen dari paginasi) agar
                // widget ringkasan tetap benar walau hanya satu halaman dimuat.
                $this->hitungRingkasan($tanggalMulai, $tanggalSelesai, $jenis);

                return $this->paginatedRecords($tanggalMulai, $tanggalSelesai, $jenis, $page, $recordsPerPage);
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
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(40),
                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Kas Masuk' => 'success',
                        'Kas Keluar' => 'danger',
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
                        'masuk' => 'Kas Masuk (Debit)',
                        'keluar' => 'Kas Keluar (Kredit)',
                    ]),
                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('tanggal_mulai')
                            ->label('Dari Tanggal')
                            ->default(now()->startOfMonth()),
                        DatePicker::make('tanggal_selesai')
                            ->label('Sampai Tanggal')
                            ->default(now()),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['tanggal_mulai'] ?? null) {
                            $indicators[] = 'Dari: '.Carbon::parse($data['tanggal_mulai'])->translatedFormat('d M Y');
                        }

                        if ($data['tanggal_selesai'] ?? null) {
                            $indicators[] = 'Sampai: '.Carbon::parse($data['tanggal_selesai'])->translatedFormat('d M Y');
                        }

                        return $indicators;
                    }),
            ])
            ->deferFilters(false)
            ->paginated([10, 25, 50, 100])
            ->emptyStateHeading('Tidak ada data')
            ->emptyStateDescription('Silakan pilih rentang tanggal untuk melihat data kas.')
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
     * Build SEMUA baris kas masuk/keluar (tanpa paginasi) untuk diekspor ke PDF.
     * Juga menyegarkan $summary lewat SQL SUM. Hanya dipakai oleh cetak PDF yang
     * memang membutuhkan seluruh baris; tampilan layar memakai paginatedRecords().
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function buildRows(?string $tanggalMulai, ?string $tanggalSelesai, ?string $jenis = null): Collection
    {
        if (! $tanggalMulai || ! $tanggalSelesai) {
            $this->summary = [];

            return collect();
        }

        $this->hitungRingkasan($tanggalMulai, $tanggalSelesai, $jenis);

        return $this->unionQuery($tanggalMulai, $tanggalSelesai, $jenis)
            ->orderBy('tanggal')
            ->orderBy('nomor_bukti')
            ->get()
            ->map(fn ($row): array => $this->mapRow($row))
            ->values();
    }

    /**
     * Halaman baris kas (gabungan masuk+keluar) yang ditampilkan, dipaginasi di
     * level DB via UNION + LIMIT/OFFSET sehingga hanya satu halaman yang dimuat
     * ke memori — bukan seluruh tabel (temuan performa #98).
     *
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    protected function paginatedRecords(?string $tanggalMulai, ?string $tanggalSelesai, ?string $jenis, int $page, int $recordsPerPage): LengthAwarePaginator
    {
        if (! $tanggalMulai || ! $tanggalSelesai) {
            return new LengthAwarePaginator([], total: 0, perPage: $recordsPerPage, currentPage: $page);
        }

        $base = $this->unionQuery($tanggalMulai, $tanggalSelesai, $jenis);

        $total = (clone $base)->count();

        $rows = $base
            ->orderBy('tanggal')
            ->orderBy('nomor_bukti')
            ->forPage($page, $recordsPerPage)
            ->get()
            ->map(fn ($row): array => $this->mapRow($row))
            ->all();

        return new LengthAwarePaginator(
            $rows,
            total: $total,
            perPage: $recordsPerPage,
            currentPage: $page,
        );
    }

    /**
     * Hitung total masuk/keluar + jumlah baris langsung di SQL (SUM/COUNT),
     * tanpa memuat baris ke Collection (temuan performa #98).
     */
    protected function hitungRingkasan(?string $tanggalMulai, ?string $tanggalSelesai, ?string $jenis = null): void
    {
        if (! $tanggalMulai || ! $tanggalSelesai) {
            $this->summary = [];

            return;
        }

        $masuk = ['nominal' => 0.0, 'jumlah' => 0];
        $keluar = ['nominal' => 0.0, 'jumlah' => 0];

        if (! $jenis || $jenis === 'masuk') {
            $agg = KasMasuk::query()
                ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
                ->selectRaw('COALESCE(SUM(nominal), 0) as total, COUNT(*) as jumlah')
                ->first();
            $masuk = ['nominal' => (float) $agg->total, 'jumlah' => (int) $agg->jumlah];
        }

        if (! $jenis || $jenis === 'keluar') {
            $agg = KasKeluar::query()
                ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
                ->selectRaw('COALESCE(SUM(nominal), 0) as total, COUNT(*) as jumlah')
                ->first();
            $keluar = ['nominal' => (float) $agg->total, 'jumlah' => (int) $agg->jumlah];
        }

        $this->summary = [
            'total_masuk' => $masuk['nominal'],
            'total_keluar' => $keluar['nominal'],
            'selisih' => $masuk['nominal'] - $keluar['nominal'],
            'jml_masuk' => $masuk['jumlah'],
            'jml_keluar' => $keluar['jumlah'],
        ];
    }

    /**
     * Query UNION ALL ternormalisasi atas kas_masuks + kas_keluars dengan nama
     * akun di-join (hindari N+1) dan keterangan yang sudah dipilih per jenis.
     * Mengembalikan query builder mentah agar bisa di-COUNT & dipaginasi di DB.
     */
    protected function unionQuery(?string $tanggalMulai, ?string $tanggalSelesai, ?string $jenis = null): QueryBuilder
    {
        $masuk = DB::table('kas_masuks')
            ->leftJoin('akuns', 'akuns.id', '=', 'kas_masuks.akun_id')
            ->whereBetween('kas_masuks.tanggal', [$tanggalMulai, $tanggalSelesai])
            ->whereNull('kas_masuks.deleted_at')
            ->selectRaw("kas_masuks.tanggal as tanggal, kas_masuks.nomor_bukti as nomor_bukti, COALESCE(akuns.nama, '-') as akun, COALESCE(kas_masuks.sumber, kas_masuks.keterangan, '-') as keterangan, 'Kas Masuk' as jenis, kas_masuks.nominal as nominal");

        $keluar = DB::table('kas_keluars')
            ->leftJoin('akuns', 'akuns.id', '=', 'kas_keluars.akun_id')
            ->whereBetween('kas_keluars.tanggal', [$tanggalMulai, $tanggalSelesai])
            ->whereNull('kas_keluars.deleted_at')
            ->selectRaw("kas_keluars.tanggal as tanggal, kas_keluars.nomor_bukti as nomor_bukti, COALESCE(akuns.nama, '-') as akun, COALESCE(kas_keluars.penerima, kas_keluars.keterangan, '-') as keterangan, 'Kas Keluar' as jenis, kas_keluars.nominal as nominal");

        if ($jenis === 'masuk') {
            return $masuk;
        }

        if ($jenis === 'keluar') {
            return $keluar;
        }

        return $masuk->unionAll($keluar);
    }

    /**
     * Normalisasi satu baris hasil union (stdClass) menjadi array kolom tabel.
     *
     * @return array<string, mixed>
     */
    protected function mapRow(object $row): array
    {
        return [
            'tanggal' => Carbon::parse($row->tanggal)->format('Y-m-d'),
            'nomor_bukti' => $row->nomor_bukti,
            'akun' => $row->akun ?? '-',
            'keterangan' => $row->keterangan ?? '-',
            'jenis' => $row->jenis,
            'nominal' => $row->nominal,
        ];
    }

    public function cetakPdf(): StreamedResponse
    {
        $tanggalFilter = $this->getTableFilterState('tanggal') ?? [];
        $jenisFilter = $this->getTableFilterState('jenis') ?? [];

        $tanggalMulai = $tanggalFilter['tanggal_mulai'] ?? null;
        $tanggalSelesai = $tanggalFilter['tanggal_selesai'] ?? null;
        $jenis = $jenisFilter['value'] ?? null;

        $rows = $this->buildRows($tanggalMulai, $tanggalSelesai, $jenis);

        $baris = $rows
            ->map(fn (array $row): array => [
                Carbon::parse($row['tanggal'])->format('d/m/Y'),
                $row['nomor_bukti'],
                $row['akun'],
                $row['keterangan'],
                $row['jenis'],
                number_format((float) $row['nominal'], 0, ',', '.'),
            ])
            ->values()
            ->all();

        $ringkasan = [
            ['TOTAL KAS MASUK', '', '', '', '', number_format((float) ($this->summary['total_masuk'] ?? 0), 0, ',', '.')],
            ['TOTAL KAS KELUAR', '', '', '', '', number_format((float) ($this->summary['total_keluar'] ?? 0), 0, ',', '.')],
            ['SELISIH', '', '', '', '', number_format((float) ($this->summary['selisih'] ?? 0), 0, ',', '.')],
        ];

        $periode = ($tanggalMulai && $tanggalSelesai)
            ? 'Periode '.Carbon::parse($tanggalMulai)->translatedFormat('d M Y')
                .' s.d. '.Carbon::parse($tanggalSelesai)->translatedFormat('d M Y')
            : 'Semua Periode';

        $pdf = LaporanPdfService::make()
            ->judul('LAPORAN DEBIT & KREDIT (KAS)')
            ->periode($periode)
            ->kolom([
                'Tanggal',
                'No. Bukti',
                'Akun',
                'Keterangan',
                'Jenis',
                ['Nominal (Rp)', 'right'],
            ])
            ->baris($baris)
            ->ringkasan($ringkasan)
            ->landscape()
            ->render();

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            LaporanPdfService::make()->namaFile('debit-kredit-kas-'.($tanggalSelesai ?? now()->toDateString())),
        );
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanDebitKreditStats::make([
                'summary' => $this->summary,
            ]),
        ];
    }
}
