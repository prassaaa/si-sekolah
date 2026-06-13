<?php

namespace App\Filament\Pages;

use App\Models\SarprasBarang;
use App\Models\SarprasKategori;
use App\Services\Accounting\LaporanPdfService;
use App\Services\Sarpras\PenyusutanService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanPenyusutan extends Page implements HasSchemas, HasTable
{
    use HasPageShield;
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-trending-down';

    protected static ?string $navigationLabel = 'Laporan Penyusutan';

    protected static \UnitEnum|string|null $navigationGroup = 'Sarana & Prasarana';

    protected static ?int $navigationSort = 12;

    protected static ?string $slug = 'sarpras/laporan/penyusutan';

    /**
     * @var array<string, string>
     */
    public array $summary = [];

    /**
     * Baris tabel terakhir yang dirender, dipakai untuk ekspor PDF.
     *
     * @var array<int, array<string, string>>
     */
    public array $rows = [];

    /**
     * Tanggal acuan akumulasi/nilai buku terakhir yang dirender (Y-m-d).
     */
    public ?string $perTanggal = null;

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Penyusutan Aset';
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
                $kategoriId = $filters['kategori_id']['value'] ?? null;

                $service = app(PenyusutanService::class);

                $sampai = filled($filters['per_tanggal']['per_tanggal'] ?? null)
                    ? Carbon::parse($filters['per_tanggal']['per_tanggal'])->endOfDay()
                    : Carbon::now();

                $this->perTanggal = $sampai->toDateString();

                $totalPerolehan = '0.00';
                $totalAkumulasi = '0.00';
                $totalNilaiBuku = '0.00';

                $records = SarprasBarang::query()
                    ->with('kategori')
                    ->whereNull('deleted_at')
                    ->where('tipe', 'aset')
                    ->when($kategoriId, fn ($q) => $q->where('sarpras_kategori_id', $kategoriId))
                    ->orderBy('nama')
                    ->get()
                    ->mapWithKeys(function (SarprasBarang $barang) use ($service, $sampai, &$totalPerolehan, &$totalAkumulasi, &$totalNilaiBuku): array {
                        $perolehan = (string) $barang->harga_perolehan;
                        $akumulasi = $service->akumulasiSampai($barang, $sampai);
                        $nilaiBuku = $service->nilaiBuku($barang, $sampai);
                        $perBulan = $service->penyusutanPerBulan($barang);

                        $totalPerolehan = bcadd($totalPerolehan, $perolehan, 2);
                        $totalAkumulasi = bcadd($totalAkumulasi, $akumulasi, 2);
                        $totalNilaiBuku = bcadd($totalNilaiBuku, $nilaiBuku, 2);

                        return [$barang->getKey() => [
                            'nama' => $barang->nama,
                            'kategori' => $barang->kategori?->nama ?? '-',
                            'metode_susut' => match ($barang->metode_susut) {
                                'garis_lurus' => 'Garis Lurus',
                                'saldo_menurun' => 'Saldo Menurun',
                                default => 'Tanpa',
                            },
                            'harga_perolehan' => $this->rupiah($perolehan),
                            'per_bulan' => $this->rupiah($perBulan),
                            'akumulasi' => $this->rupiah($akumulasi),
                            'nilai_buku' => $this->rupiah($nilaiBuku),
                        ]];
                    });

                $this->summary = [
                    'total_perolehan' => $this->rupiah($totalPerolehan),
                    'total_akumulasi' => $this->rupiah($totalAkumulasi),
                    'total_nilai_buku' => $this->rupiah($totalNilaiBuku),
                ];

                $this->rows = $records->values()->all();

                return $records;
            })
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama Barang')
                    ->sortable(),
                TextColumn::make('kategori')
                    ->label('Kategori'),
                TextColumn::make('metode_susut')
                    ->label('Metode'),
                TextColumn::make('harga_perolehan')
                    ->label('Harga Perolehan')
                    ->alignEnd(),
                TextColumn::make('per_bulan')
                    ->label('Penyusutan/Bulan')
                    ->alignEnd(),
                TextColumn::make('akumulasi')
                    ->label('Akumulasi')
                    ->alignEnd()
                    ->color('warning'),
                TextColumn::make('nilai_buku')
                    ->label('Nilai Buku')
                    ->alignEnd()
                    ->weight('bold')
                    ->color('success'),
            ])
            ->filters([
                SelectFilter::make('kategori_id')
                    ->label('Kategori')
                    ->options(
                        SarprasKategori::query()
                            ->where('is_active', true)
                            ->orderBy('nama')
                            ->pluck('nama', 'id'),
                    ),
                Filter::make('per_tanggal')
                    ->form([
                        DatePicker::make('per_tanggal')
                            ->label('Per Tanggal')
                            ->helperText('Akumulasi & nilai buku dihitung sampai tanggal ini.')
                            ->default(now()),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (! ($data['per_tanggal'] ?? null)) {
                            return null;
                        }

                        return 'Per: '.Carbon::parse($data['per_tanggal'])->translatedFormat('d M Y');
                    }),
            ])
            ->deferFilters(false)
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada data')
            ->emptyStateDescription('Tidak ada aset untuk dihitung penyusutannya.')
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
        $tanggal = $this->perTanggal ? Carbon::parse($this->perTanggal) : now();

        $baris = array_map(fn (array $row): array => [
            $row['nama'],
            $row['kategori'],
            $row['metode_susut'],
            $row['harga_perolehan'],
            $row['per_bulan'],
            $row['akumulasi'],
            $row['nilai_buku'],
        ], $this->rows);

        $pdf = LaporanPdfService::make()
            ->judul('Laporan Penyusutan Aset')
            ->periode('Per '.$tanggal->translatedFormat('d F Y'))
            ->landscape()
            ->kolom([
                'Nama Barang',
                'Kategori',
                'Metode',
                ['Harga Perolehan', 'right'],
                ['Penyusutan/Bulan', 'right'],
                ['Akumulasi', 'right'],
                ['Nilai Buku', 'right'],
            ])
            ->baris($baris)
            ->ringkasan([[
                'TOTAL',
                '',
                '',
                $this->summary['total_perolehan'] ?? 'Rp 0',
                '',
                $this->summary['total_akumulasi'] ?? 'Rp 0',
                $this->summary['total_nilai_buku'] ?? 'Rp 0',
            ]])
            ->render();

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            LaporanPdfService::make()->namaFile('laporan-penyusutan-'.$tanggal->format('Y-m-d')),
        );
    }

    private function rupiah(string $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
