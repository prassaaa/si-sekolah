<?php

namespace App\Filament\Pages;

use App\Models\Akun;
use App\Models\Anggaran;
use App\Models\JurnalUmum;
use App\Models\TahunAjaran;
use App\Services\Accounting\LaporanPdfService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
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

class LaporanRapbs extends Page implements HasSchemas, HasTable
{
    use HasPageShield;
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static \UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 120;

    protected static ?string $title = 'Laporan RAPBS';

    protected static ?string $navigationLabel = 'Laporan RAPBS';

    public function getTitle(): string|Htmlable
    {
        return 'Laporan RAPBS / Anggaran vs Realisasi';
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
                $tahunAjaranId = $filters['tahun_ajaran']['tahun_ajaran_id'] ?? null;

                return $this->buildRows($tahunAjaranId);
            })
            ->columns([
                TextColumn::make('akun')
                    ->label('Akun'),

                TextColumn::make('seksi')
                    ->label('Seksi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pendapatan' => 'success',
                        'Beban' => 'danger',
                        'Total Pendapatan' => 'success',
                        'Total Beban' => 'danger',
                        'Surplus / Defisit' => 'primary',
                        default => 'gray',
                    }),

                TextColumn::make('anggaran')
                    ->label('Anggaran (Rp)')
                    ->money('IDR')
                    ->alignEnd(),

                TextColumn::make('realisasi')
                    ->label('Realisasi (Rp)')
                    ->money('IDR')
                    ->alignEnd(),

                TextColumn::make('selisih')
                    ->label('Selisih (Rp)')
                    ->money('IDR')
                    ->alignEnd()
                    ->color(fn ($state): string => (float) $state >= 0 ? 'success' : 'danger'),

                TextColumn::make('persen_serapan')
                    ->label('% Serapan')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 1).'%'),
            ])
            ->filters([
                Filter::make('tahun_ajaran')
                    ->form([
                        Select::make('tahun_ajaran_id')
                            ->label('Tahun Ajaran')
                            ->options(
                                TahunAjaran::query()
                                    ->orderByDesc('tanggal_mulai')
                                    ->pluck('nama', 'id')
                            )
                            ->default(fn () => TahunAjaran::getActive()?->id)
                            ->searchable()
                            ->preload(),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (! ($data['tahun_ajaran_id'] ?? null)) {
                            return null;
                        }

                        $nama = TahunAjaran::find($data['tahun_ajaran_id'])?->nama;

                        return $nama ? "Tahun Ajaran: {$nama}" : null;
                    }),
            ])
            ->deferFilters(false)
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada data anggaran')
            ->emptyStateDescription('Pilih tahun ajaran dan pastikan data anggaran sudah diinput.')
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
     * Build baris laporan RAPBS: anggaran vs realisasi per akun, dipisah per seksi.
     *
     * Realisasi dihitung dari mutasi jurnal_umums akun tsb sepanjang rentang tanggal
     * tahun ajaran. Untuk akun pendapatan (kredit-normal): realisasi = SUM(kredit) - SUM(debit).
     * Untuk akun beban (debit-normal): realisasi = SUM(debit) - SUM(kredit).
     * Selisih pendapatan: anggaran - realisasi (sisa anggaran, positif = under-budget).
     * Selisih beban: anggaran - realisasi (positif = hemat/under-spent).
     * % Serapan = (realisasi / anggaran) * 100, 0 jika anggaran = 0.
     *
     * @return Collection<int, array{akun: string, seksi: string, anggaran: float, realisasi: float, selisih: float, persen_serapan: float}>
     */
    public function buildRows(?int $tahunAjaranId): Collection
    {
        $tahunAjaran = $tahunAjaranId
            ? TahunAjaran::find($tahunAjaranId)
            : TahunAjaran::getActive();

        if (! $tahunAjaran) {
            return collect();
        }

        $anggarans = Anggaran::query()
            ->where('tahun_ajaran_id', $tahunAjaran->id)
            ->with(['akun'])
            ->get();

        if ($anggarans->isEmpty()) {
            return collect();
        }

        $tanggalMulai = $tahunAjaran->tanggal_mulai->toDateString();
        $tanggalAkhir = $tahunAjaran->tanggal_selesai->toDateString();

        // Hitung realisasi per akun dari jurnal dalam rentang tanggal TA
        $akunIds = $anggarans->pluck('akun_id')->all();
        $realisasiPerAkun = $this->hitungRealisasiPerAkun($akunIds, $tanggalMulai, $tanggalAkhir);

        $rows = collect();

        foreach (['pendapatan', 'beban'] as $tipe) {
            $seksiLabel = ucfirst($tipe);
            $barisPerTipe = $anggarans
                ->filter(fn (Anggaran $a) => $a->akun?->tipe === $tipe)
                ->sortBy('akun.kode');

            if ($barisPerTipe->isEmpty()) {
                continue;
            }

            $totalAnggaran = 0.0;
            $totalRealisasi = 0.0;

            foreach ($barisPerTipe as $anggaran) {
                $anggaranNominal = (float) $anggaran->nominal_anggaran;
                $realisasi = (float) ($realisasiPerAkun[$anggaran->akun_id] ?? 0);
                $selisih = $anggaranNominal - $realisasi;
                $persenSerapan = $anggaranNominal > 0
                    ? ($realisasi / $anggaranNominal) * 100
                    : 0.0;

                $rows->push([
                    'akun' => ($anggaran->akun->kode ?? '').' - '.($anggaran->akun->nama ?? '-'),
                    'seksi' => $seksiLabel,
                    'anggaran' => $anggaranNominal,
                    'realisasi' => $realisasi,
                    'selisih' => $selisih,
                    'persen_serapan' => $persenSerapan,
                ]);

                $totalAnggaran += $anggaranNominal;
                $totalRealisasi += $realisasi;
            }

            $totalSelisih = $totalAnggaran - $totalRealisasi;
            $totalPersen = $totalAnggaran > 0
                ? ($totalRealisasi / $totalAnggaran) * 100
                : 0.0;

            $rows->push([
                'akun' => 'TOTAL '.strtoupper($seksiLabel),
                'seksi' => 'Total '.ucfirst($tipe),
                'anggaran' => $totalAnggaran,
                'realisasi' => $totalRealisasi,
                'selisih' => $totalSelisih,
                'persen_serapan' => $totalPersen,
            ]);
        }

        return $rows->values();
    }

    /**
     * Hitung realisasi jurnal per akun_id dalam rentang tanggal tahun ajaran.
     *
     * Menggunakan SQL SUM+GROUP BY langsung ke jurnal_umums dengan join ke akuns
     * untuk menentukan posisi normal (kredit = pendapatan, debit = beban).
     * Soft-deleted akun di-include (join tanpa filter deleted_at) agar konsisten
     * dengan FinancialService.
     *
     * @param  array<int>  $akunIds
     * @return array<int, float>
     */
    private function hitungRealisasiPerAkun(array $akunIds, string $tanggalMulai, string $tanggalAkhir): array
    {
        if (empty($akunIds)) {
            return [];
        }

        $rows = JurnalUmum::query()
            ->join('akuns', 'akuns.id', '=', 'jurnal_umums.akun_id')
            ->whereIn('jurnal_umums.akun_id', $akunIds)
            ->whereBetween('jurnal_umums.tanggal', [$tanggalMulai, $tanggalAkhir])
            ->groupBy('jurnal_umums.akun_id', 'akuns.posisi_normal')
            ->selectRaw(
                'jurnal_umums.akun_id, akuns.posisi_normal, '.
                'COALESCE(SUM(jurnal_umums.debit), 0) as total_debit, '.
                'COALESCE(SUM(jurnal_umums.kredit), 0) as total_kredit'
            )
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $isDebitNormal = $row->posisi_normal === 'debit';
            $result[(int) $row->akun_id] = $isDebitNormal
                ? (float) $row->total_debit - (float) $row->total_kredit
                : (float) $row->total_kredit - (float) $row->total_debit;
        }

        return $result;
    }

    public function cetakPdf(): StreamedResponse
    {
        $filterState = $this->getTableFilterState('tahun_ajaran') ?? [];
        $tahunAjaranId = $filterState['tahun_ajaran_id'] ?? null;

        $tahunAjaran = $tahunAjaranId
            ? TahunAjaran::find($tahunAjaranId)
            : TahunAjaran::getActive();

        $rows = $this->buildRows($tahunAjaranId ? (int) $tahunAjaranId : null);

        $isTotalRow = fn (array $row): bool => str_starts_with($row['akun'], 'TOTAL ');

        $baris = $rows
            ->reject($isTotalRow)
            ->map(fn (array $row): array => [
                $row['akun'],
                $row['seksi'],
                number_format($row['anggaran'], 0, ',', '.'),
                number_format($row['realisasi'], 0, ',', '.'),
                number_format($row['selisih'], 0, ',', '.'),
                number_format($row['persen_serapan'], 1).'%',
            ])
            ->values()
            ->all();

        $ringkasan = $rows
            ->filter($isTotalRow)
            ->map(fn (array $row): array => [
                $row['akun'],
                '',
                number_format($row['anggaran'], 0, ',', '.'),
                number_format($row['realisasi'], 0, ',', '.'),
                number_format($row['selisih'], 0, ',', '.'),
                number_format($row['persen_serapan'], 1).'%',
            ])
            ->values()
            ->all();

        $periode = $tahunAjaran
            ? "Tahun Ajaran {$tahunAjaran->nama}"
            : 'Semua Tahun Ajaran';

        $pdf = LaporanPdfService::make()
            ->judul('LAPORAN RAPBS / ANGGARAN VS REALISASI')
            ->periode($periode)
            ->kolom([
                'Akun',
                'Seksi',
                ['Anggaran (Rp)', 'right'],
                ['Realisasi (Rp)', 'right'],
                ['Selisih (Rp)', 'right'],
                ['% Serapan', 'right'],
            ])
            ->baris($baris)
            ->ringkasan($ringkasan)
            ->landscape()
            ->render();

        $namaFile = 'rapbs-'.($tahunAjaran?->kode ?? now()->toDateString());

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            LaporanPdfService::make()->namaFile($namaFile),
        );
    }
}
