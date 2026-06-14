<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanPembayaranStats;
use App\Models\JenisPembayaran;
use App\Models\Semester;
use App\Models\TagihanSiswa;
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
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanPembayaran extends Page implements HasSchemas, HasTable
{
    use HasPageShield;
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Laporan Pembayaran';

    protected static ?string $slug = 'laporan/pembayaran';

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Rekap Pembayaran';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    /**
     * Bangun rekap pembayaran per jenis tagihan untuk satu semester.
     *
     * Dua metrik nominal sengaja dipisahkan agar tidak menyesatkan (#73/#78):
     *   - "terbayar_periode": SUM pembayaran berhasil yang TERFILTER rentang
     *     tanggal (arus kas masuk pada periode ini).
     *   - "sisa": SUM kolom sisa_tagihan RIIL (posisi terkini), bukan
     *     total_tagihan dikurangi pembayaran periode. Dengan begitu "Sisa
     *     Tagihan" mencerminkan piutang sebenarnya, lepas dari filter tanggal.
     *
     * Tagihan berstatus "batal" dikecualikan dari seluruh agregat.
     *
     * @return Collection<int, array{
     *     jenis: string,
     *     jumlah_siswa: int,
     *     total_tagihan: string,
     *     terbayar_periode: string,
     *     sisa: string,
     *     lunas: int,
     *     belum_lunas: int
     * }>
     */
    public function buildData(?int $semesterId, ?int $jenisPembayaranId, ?string $tanggalMulai, ?string $tanggalSelesai): Collection
    {
        if (! $semesterId) {
            $this->summary = [];

            return collect();
        }

        $query = TagihanSiswa::query()
            ->with(['siswa.kelas', 'jenisPembayaran', 'pembayarans'])
            ->where('semester_id', $semesterId)
            ->where('status', '!=', 'batal');

        if (filled($jenisPembayaranId)) {
            $query->where('jenis_pembayaran_id', $jenisPembayaranId);
        }

        $tagihans = $query->get();

        $mulai = $tanggalMulai ? Carbon::parse($tanggalMulai)->startOfDay() : null;
        $selesai = $tanggalSelesai ? Carbon::parse($tanggalSelesai)->endOfDay() : null;

        $data = $tagihans->groupBy('jenis_pembayaran_id')->map(function ($items) use ($mulai, $selesai) {
            $jenis = $items->first()->jenisPembayaran;

            $pembayaransBerhasil = $items
                ->flatMap(fn ($tagihan) => $tagihan->pembayarans)
                ->where('status', 'berhasil');

            if ($mulai) {
                $pembayaransBerhasil = $pembayaransBerhasil->filter(fn ($pembayaran) => $pembayaran->tanggal_bayar >= $mulai);
            }

            if ($selesai) {
                $pembayaransBerhasil = $pembayaransBerhasil->filter(fn ($pembayaran) => $pembayaran->tanggal_bayar <= $selesai);
            }

            return [
                'jenis' => $jenis?->nama ?? '-',
                'jumlah_siswa' => $items->pluck('siswa_id')->unique()->count(),
                'total_tagihan' => (string) $items->sum('total_tagihan'),
                'terbayar_periode' => (string) $pembayaransBerhasil->sum('jumlah_bayar'),
                'sisa' => (string) $items->sum('sisa_tagihan'),
                'lunas' => $items->where('status', 'lunas')->count(),
                'belum_lunas' => $items->whereIn('status', ['belum_bayar', 'sebagian'])->count(),
            ];
        })->values();

        $totalTagihan = (string) $data->sum(fn (array $row): float => (float) $row['total_tagihan']);
        $terbayarPeriode = (string) $data->sum(fn (array $row): float => (float) $row['terbayar_periode']);
        $totalSisa = (string) $data->sum(fn (array $row): float => (float) $row['sisa']);

        $this->summary = [
            'total_tagihan' => $totalTagihan,
            'terbayar_periode' => $terbayarPeriode,
            'total_sisa' => $totalSisa,
            'persentase' => (float) $totalTagihan > 0
                ? round((((float) $totalTagihan - (float) $totalSisa) / (float) $totalTagihan) * 100, 1)
                : 0,
        ];

        return $data;
    }

    public function table(Table $table): Table
    {
        $activeSemesterId = Semester::query()->where('is_active', true)->value('id');

        return $table
            ->records(function (array $filters) use ($activeSemesterId): Collection {
                return $this->buildData(
                    $filters['semester_id']['value'] ?? $activeSemesterId,
                    $filters['jenis_pembayaran_id']['value'] ?? null,
                    $filters['tanggal']['tanggal_mulai'] ?? null,
                    $filters['tanggal']['tanggal_selesai'] ?? null,
                );
            })
            ->columns([
                TextColumn::make('jenis')
                    ->label('Jenis Pembayaran'),
                TextColumn::make('jumlah_siswa')
                    ->label('Siswa')
                    ->alignCenter(),
                TextColumn::make('total_tagihan')
                    ->label('Tagihan')
                    ->money('IDR')
                    ->alignEnd(),
                TextColumn::make('terbayar_periode')
                    ->label('Terbayar (periode ini)')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('success'),
                TextColumn::make('sisa')
                    ->label('Sisa Tagihan')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('danger'),
                TextColumn::make('lunas')
                    ->label('Lunas')
                    ->alignCenter()
                    ->color('success'),
                TextColumn::make('belum_lunas')
                    ->label('Belum Lunas')
                    ->alignCenter()
                    ->color('danger'),
            ])
            ->filters([
                SelectFilter::make('semester_id')
                    ->label('Semester')
                    ->options(Semester::query()->orderByDesc('tahun_ajaran_id')->pluck('nama', 'id'))
                    ->default($activeSemesterId),
                SelectFilter::make('jenis_pembayaran_id')
                    ->label('Jenis Pembayaran')
                    ->options(JenisPembayaran::query()->where('is_active', true)->pluck('nama', 'id')),
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
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada data')
            ->emptyStateDescription('Silakan pilih semester untuk melihat data pembayaran.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanPembayaranStats::make([
                'summary' => $this->summary,
            ]),
        ];
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
        $activeSemesterId = Semester::query()->where('is_active', true)->value('id');

        $semesterId = $this->getTableFilterState('semester_id')['value'] ?? $activeSemesterId;
        $jenisPembayaranId = $this->getTableFilterState('jenis_pembayaran_id')['value'] ?? null;
        $tanggal = $this->getTableFilterState('tanggal') ?? [];
        $tanggalMulai = $tanggal['tanggal_mulai'] ?? null;
        $tanggalSelesai = $tanggal['tanggal_selesai'] ?? null;

        $rows = $this->buildData($semesterId, $jenisPembayaranId, $tanggalMulai, $tanggalSelesai);
        $summary = $this->summary;

        $baris = $rows->map(function (array $row): array {
            return [
                $row['jenis'],
                (string) $row['jumlah_siswa'],
                'Rp '.number_format((float) $row['total_tagihan'], 0, ',', '.'),
                'Rp '.number_format((float) $row['terbayar_periode'], 0, ',', '.'),
                'Rp '.number_format((float) $row['sisa'], 0, ',', '.'),
            ];
        })->toArray();

        $semesterNama = $semesterId
            ? Semester::query()->find($semesterId)?->nama
            : null;

        $pdf = LaporanPdfService::make()
            ->judul('LAPORAN REKAP PEMBAYARAN')
            ->periode($this->periodeLabel($semesterNama, $tanggalMulai, $tanggalSelesai))
            ->kolom([
                'Jenis Pembayaran',
                ['Siswa', 'center'],
                ['Tagihan', 'right'],
                ['Terbayar (periode ini)', 'right'],
                ['Sisa Tagihan', 'right'],
            ])
            ->baris($baris)
            ->ringkasan([
                [
                    'TOTAL',
                    '',
                    'Rp '.number_format((float) ($summary['total_tagihan'] ?? 0), 0, ',', '.'),
                    'Rp '.number_format((float) ($summary['terbayar_periode'] ?? 0), 0, ',', '.'),
                    'Rp '.number_format((float) ($summary['total_sisa'] ?? 0), 0, ',', '.'),
                ],
            ])
            ->catatan('"Terbayar (periode ini)" = pembayaran berhasil pada rentang tanggal terpilih. "Sisa Tagihan" = sisa tagihan riil posisi terkini (tidak terpengaruh filter tanggal).')
            ->landscape()
            ->render();

        $namaFile = LaporanPdfService::make()
            ->namaFile('laporan-rekap-pembayaran-'.now()->format('Ymd'));

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $namaFile,
        );
    }

    private function periodeLabel(?string $semesterNama, ?string $tanggalMulai, ?string $tanggalSelesai): ?string
    {
        $bagian = [];

        if ($semesterNama) {
            $bagian[] = 'Semester '.$semesterNama;
        }

        if ($tanggalMulai && $tanggalSelesai) {
            $bagian[] = Carbon::parse($tanggalMulai)->translatedFormat('d F Y')
                .' s/d '.Carbon::parse($tanggalSelesai)->translatedFormat('d F Y');
        }

        return $bagian === [] ? null : implode(' — ', $bagian);
    }
}
