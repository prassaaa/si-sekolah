<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanPembayaranPerKelasStats;
use App\Models\Kelas;
use App\Models\KenaikanKelas;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\TagihanSiswa;
use App\Services\Accounting\LaporanPdfService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanPembayaranPerKelas extends Page implements HasSchemas, HasTable
{
    use HasPageShield;
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Pembayaran Per Kelas';

    protected static ?string $slug = 'laporan/pembayaran-per-kelas';

    public array $summary = [];

    public ?string $kelasNama = null;

    /**
     * Basis pemetaan siswa-ke-kelas yang dipakai pada hasil saat ini:
     * "current" (kelas siswa saat ini) atau "historis" (kelas asal pada
     * semester terpilih, dari data kenaikan kelas). Ditampilkan sebagai catatan
     * agar pembaca paham dasar pengelompokan (#97).
     */
    public ?string $basisKelas = null;

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Pembayaran Per Kelas';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    /**
     * Kumpulkan id siswa yang berada di $kelasId pada $semesterId.
     *
     * Untuk semester AKTIF, keanggotaan kelas diambil dari kolom kelas_id siswa
     * saat ini (akurat dan murah). Untuk semester HISTORIS, siswa kemungkinan
     * sudah naik kelas, sehingga keanggotaan diambil dari data KenaikanKelas
     * (kelas_asal_id = kelas siswa pada semester tersebut). Siswa yang punya
     * tagihan pada semester itu namun tidak memiliki baris KenaikanKelas
     * di-fallback ke kelas_id saat ini agar tidak hilang dari laporan.
     *
     * Efek samping: mengeset $this->basisKelas ('current' | 'historis').
     *
     * @return array<int>
     */
    private function siswaIdsDiKelas(int $semesterId, int $kelasId): array
    {
        $semester = Semester::query()->find($semesterId);

        if ($semester !== null && (bool) $semester->is_active) {
            $this->basisKelas = 'current';

            return Siswa::query()
                ->where('kelas_id', $kelasId)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }

        $this->basisKelas = 'historis';

        // Siswa yang menurut data kenaikan kelas berada di kelas ini pada
        // semester tersebut (kelas_asal_id).
        $historis = KenaikanKelas::query()
            ->where('semester_id', $semesterId)
            ->where('kelas_asal_id', $kelasId)
            ->pluck('siswa_id')
            ->map(fn ($id): int => (int) $id);

        // Siswa yang punya tagihan di semester ini tapi tidak punya catatan
        // kenaikan kelas sama sekali pada semester ini: gunakan kelas_id terkini
        // sebagai fallback.
        $siswaBerKenaikan = KenaikanKelas::query()
            ->where('semester_id', $semesterId)
            ->pluck('siswa_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $fallback = Siswa::query()
            ->where('kelas_id', $kelasId)
            ->whereNotIn('id', $siswaBerKenaikan)
            ->whereHas('tagihanSiswas', fn ($query) => $query->where('semester_id', $semesterId))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id);

        return $historis->merge($fallback)->unique()->values()->all();
    }

    /**
     * Bangun rekap pembayaran per siswa untuk satu kelas pada satu semester.
     *
     * @return Collection<int, array{
     *     nis: string,
     *     nama: string,
     *     total_tagihan: string,
     *     total_terbayar: string,
     *     sisa: string,
     *     status: string
     * }>
     */
    public function buildData(?int $semesterId, ?int $kelasId): Collection
    {
        if (! $semesterId || ! $kelasId) {
            $this->summary = [];
            $this->kelasNama = null;
            $this->basisKelas = null;

            return collect();
        }

        $this->kelasNama = Kelas::query()->find($kelasId)?->nama;

        $siswaIds = $this->siswaIdsDiKelas($semesterId, $kelasId);

        if ($siswaIds === []) {
            $this->summary = [
                'total_siswa' => 0,
                'total_tagihan' => '0',
                'total_terbayar' => '0',
                'total_sisa' => '0',
                'lunas' => 0,
                'belum_lunas' => 0,
            ];

            return collect();
        }

        $tagihans = TagihanSiswa::query()
            ->with(['siswa', 'jenisPembayaran', 'pembayarans'])
            ->where('semester_id', $semesterId)
            ->whereIn('siswa_id', $siswaIds)
            ->where('status', '!=', 'batal')
            ->get();

        $data = $tagihans->groupBy('siswa_id')->map(function ($items) {
            $siswa = $items->first()->siswa;

            return [
                'nis' => $siswa?->nis ?? '-',
                'nama' => $siswa?->nama_lengkap ?? '-',
                'total_tagihan' => (string) $items->sum('total_tagihan'),
                'total_terbayar' => (string) $items->sum('total_terbayar'),
                'sisa' => (string) $items->sum('sisa_tagihan'),
                'status' => $items->every(fn ($tagihan) => $tagihan->status === 'lunas') ? 'Lunas' : 'Belum Lunas',
            ];
        })->sortBy('nama')->values();

        $this->summary = [
            'total_siswa' => $data->count(),
            'total_tagihan' => (string) $data->sum(fn (array $row): float => (float) $row['total_tagihan']),
            'total_terbayar' => (string) $data->sum(fn (array $row): float => (float) $row['total_terbayar']),
            'total_sisa' => (string) $data->sum(fn (array $row): float => (float) $row['sisa']),
            'lunas' => $data->where('status', 'Lunas')->count(),
            'belum_lunas' => $data->where('status', 'Belum Lunas')->count(),
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
                    $filters['kelas_id']['value'] ?? null,
                );
            })
            ->columns([
                TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable(),
                TextColumn::make('nama')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_tagihan')
                    ->label('Tagihan')
                    ->money('IDR')
                    ->alignEnd(),
                TextColumn::make('total_terbayar')
                    ->label('Terbayar')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('success'),
                TextColumn::make('sisa')
                    ->label('Sisa')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('danger'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Lunas' => 'success',
                        default => 'danger',
                    })
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('semester_id')
                    ->label('Semester')
                    ->options(Semester::query()->orderByDesc('tahun_ajaran_id')->pluck('nama', 'id'))
                    ->default($activeSemesterId),
                SelectFilter::make('kelas_id')
                    ->label('Kelas')
                    ->options(Kelas::query()->where('is_active', true)->orderBy('tingkat')->orderBy('nama')->pluck('nama', 'id')),
            ])
            ->deferFilters(false)
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada data')
            ->emptyStateDescription('Silakan pilih semester dan kelas untuk melihat data pembayaran.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    /**
     * Catatan basis pengelompokan kelas untuk ditampilkan pada laporan/PDF.
     */
    public function catatanBasisKelas(): ?string
    {
        return match ($this->basisKelas) {
            'current' => 'Pengelompokan memakai kelas siswa saat ini (semester aktif).',
            'historis' => 'Pengelompokan memakai kelas siswa pada semester terpilih berdasarkan data kenaikan kelas. Siswa tanpa data kenaikan kelas dipetakan ke kelasnya saat ini.',
            default => null,
        };
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanPembayaranPerKelasStats::make([
                'summary' => $this->summary,
                'kelasNama' => $this->kelasNama,
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
        $kelasId = $this->getTableFilterState('kelas_id')['value'] ?? null;

        $rows = $this->buildData($semesterId, $kelasId);
        $summary = $this->summary;

        $no = 1;
        $baris = $rows->map(function (array $row) use (&$no): array {
            return [
                (string) $no++,
                $row['nis'],
                $row['nama'],
                'Rp '.number_format((float) $row['total_tagihan'], 0, ',', '.'),
                'Rp '.number_format((float) $row['total_terbayar'], 0, ',', '.'),
                'Rp '.number_format((float) $row['sisa'], 0, ',', '.'),
                $row['status'],
            ];
        })->toArray();

        $semesterNama = $semesterId ? Semester::query()->find($semesterId)?->nama : null;
        $periode = 'Kelas '.($this->kelasNama ?? '-')
            .($semesterNama ? ' — Semester '.$semesterNama : '');

        $pdf = LaporanPdfService::make()
            ->judul('LAPORAN PEMBAYARAN PER KELAS')
            ->periode($periode)
            ->kolom([
                'No',
                'NIS',
                'Nama Siswa',
                ['Tagihan', 'right'],
                ['Terbayar', 'right'],
                ['Sisa', 'right'],
                ['Status', 'center'],
            ])
            ->baris($baris)
            ->ringkasan([
                [
                    '',
                    '',
                    'TOTAL',
                    'Rp '.number_format((float) ($summary['total_tagihan'] ?? 0), 0, ',', '.'),
                    'Rp '.number_format((float) ($summary['total_terbayar'] ?? 0), 0, ',', '.'),
                    'Rp '.number_format((float) ($summary['total_sisa'] ?? 0), 0, ',', '.'),
                    '',
                ],
            ])
            ->catatan($this->catatanBasisKelas())
            ->landscape()
            ->render();

        $namaFile = LaporanPdfService::make()
            ->namaFile('laporan-pembayaran-per-kelas-'.now()->format('Ymd'));

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $namaFile,
        );
    }
}
