<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanTabunganStats;
use App\Models\Kelas;
use App\Models\TabunganSiswa;
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

class LaporanTabungan extends Page implements HasSchemas, HasTable
{
    use HasPageShield;
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 60;

    protected static ?string $title = 'Laporan Tabungan';

    protected static ?string $slug = 'laporan/tabungan';

    public array $summary = [];

    /**
     * Baris tabel terakhir yang dirender, dipakai untuk membangun ekspor PDF
     * agar isi cetakan persis sama dengan yang dilihat pengguna.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $rows = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Tabungan Siswa';
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
                $query = TabunganSiswa::query()
                    ->with('siswa.kelas');

                if (filled($filters['kelas']['value'] ?? null)) {
                    $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $filters['kelas']['value']));
                }

                if (filled($filters['tanggal']['tanggal_mulai'] ?? null)) {
                    $query->where('tanggal', '>=', $filters['tanggal']['tanggal_mulai']);
                }

                if (filled($filters['tanggal']['tanggal_selesai'] ?? null)) {
                    $query->where('tanggal', '<=', $filters['tanggal']['tanggal_selesai']);
                }

                $tabungans = $query->orderBy('tanggal', 'desc')->get();

                $data = $tabungans->groupBy('siswa_id')->map(function ($items, $siswaId) {
                    $siswa = $items->first()->siswa;

                    return [
                        'nis' => $siswa?->nis ?? '-',
                        'nama' => $siswa?->nama_lengkap ?? '-',
                        'kelas' => $siswa?->kelas?->nama ?? '-',
                        'total_setor' => $items->where('jenis', 'setor')->sum('nominal'),
                        'total_tarik' => $items->where('jenis', 'tarik')->sum('nominal'),
                        'saldo' => TabunganSiswa::getSaldoSiswa((int) $siswaId),
                        'jml_transaksi' => $items->count(),
                    ];
                })->sortBy('kelas')->values();

                $this->summary = [
                    'total_siswa' => $data->count(),
                    'total_setor' => $data->sum('total_setor'),
                    'total_tarik' => $data->sum('total_tarik'),
                    'total_saldo' => $this->totalSaldoSemuaSiswa($filters['kelas']['value'] ?? null),
                ];

                $this->rows = $data->all();

                return $data;
            })
            ->columns([
                TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable(),
                TextColumn::make('nama')
                    ->label('Nama Siswa')
                    ->searchable(),
                TextColumn::make('kelas')
                    ->label('Kelas')
                    ->sortable(),
                TextColumn::make('total_setor')
                    ->label('Total Setor')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('success'),
                TextColumn::make('total_tarik')
                    ->label('Total Tarik')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('danger'),
                TextColumn::make('saldo')
                    ->label('Saldo Terkini')
                    ->money('IDR')
                    ->alignEnd()
                    ->weight('bold'),
                TextColumn::make('jml_transaksi')
                    ->label('Transaksi')
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('kelas')
                    ->label('Kelas')
                    ->options(Kelas::query()->where('is_active', true)->orderBy('tingkat')->orderBy('nama')->pluck('nama', 'id')),
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
            ->emptyStateDescription('Silakan pilih filter untuk melihat data tabungan.')
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

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanTabunganStats::make([
                'summary' => $this->summary,
            ]),
        ];
    }

    /**
     * Total saldo kewajiban tabungan = jumlah saldo terkini SELURUH siswa yang
     * pernah menabung (bukan hanya yang bertransaksi dalam rentang filter),
     * dihitung kronologis lewat getSaldoSiswa. Filter kelas tetap dihormati.
     */
    private function totalSaldoSemuaSiswa(?int $kelasId): float
    {
        $siswaIds = TabunganSiswa::query()
            ->when($kelasId, fn ($q) => $q->whereHas('siswa', fn ($s) => $s->where('kelas_id', $kelasId)))
            ->distinct()
            ->pluck('siswa_id');

        return $siswaIds->reduce(
            fn (float $total, $siswaId): float => $total + TabunganSiswa::getSaldoSiswa((int) $siswaId),
            0.0,
        );
    }

    private function cetakPdf(): StreamedResponse
    {
        $baris = array_map(fn (array $row): array => [
            $row['nis'],
            $row['nama'],
            $row['kelas'],
            $this->rupiah($row['total_setor']),
            $this->rupiah($row['total_tarik']),
            $this->rupiah($row['saldo']),
            number_format((int) $row['jml_transaksi'], 0, ',', '.'),
        ], $this->rows);

        $pdf = LaporanPdfService::make()
            ->judul('Laporan Tabungan Siswa')
            ->periode('Saldo terkini per '.now()->translatedFormat('d F Y'))
            ->landscape()
            ->kolom([
                'NIS',
                'Nama Siswa',
                'Kelas',
                ['Total Setor', 'right'],
                ['Total Tarik', 'right'],
                ['Saldo Terkini', 'right'],
                ['Transaksi', 'center'],
            ])
            ->baris($baris)
            ->ringkasan([[
                'TOTAL',
                '',
                '',
                $this->rupiah($this->summary['total_setor'] ?? 0),
                $this->rupiah($this->summary['total_tarik'] ?? 0),
                $this->rupiah($this->summary['total_saldo'] ?? 0),
                number_format((int) ($this->summary['total_siswa'] ?? 0), 0, ',', '.'),
            ]])
            ->catatan('Saldo terkini adalah saldo kronologis seluruh siswa bersaldo; total mencerminkan kewajiban titipan tabungan.')
            ->render();

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            LaporanPdfService::make()->namaFile('laporan-tabungan-'.now()->format('Y-m-d')),
        );
    }

    private function rupiah(mixed $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
