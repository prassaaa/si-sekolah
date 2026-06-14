<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanUnitPosStats;
use App\Models\Pembayaran;
use App\Models\UnitPos;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanUnitPos extends Page implements HasSchemas, HasTable
{
    use HasPageShield;
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 70;

    protected static ?string $title = 'Laporan Unit POS';

    protected static ?string $slug = 'laporan/unit-pos';

    public ?string $activeTab = null;

    public ?Model $parentRecord = null;

    public array $summary = [];

    public ?string $unitPosNama = null;

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Transaksi Unit POS';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    /**
     * Bangun daftar pembayaran berhasil unit POS untuk filter terpilih.
     *
     * @return Collection<int, Pembayaran>
     */
    public function buildData(?int $unitPosId, ?string $tanggalMulai, ?string $tanggalSelesai): Collection
    {
        $query = Pembayaran::query()
            ->with(['tagihanSiswa.siswa', 'tagihanSiswa.jenisPembayaran'])
            ->where('status', 'berhasil');

        if (filled($unitPosId)) {
            $query->where('unit_pos_id', $unitPosId);
            $this->unitPosNama = UnitPos::query()->find($unitPosId)?->nama;
        } else {
            $this->unitPosNama = null;
        }

        if (filled($tanggalMulai)) {
            $query->whereDate('tanggal_bayar', '>=', $tanggalMulai);
        }

        if (filled($tanggalSelesai)) {
            $query->whereDate('tanggal_bayar', '<=', $tanggalSelesai);
        }

        $records = $query->orderByDesc('tanggal_bayar')->get();

        $this->summary = [
            'total_unit' => $records->pluck('unit_pos_id')->filter()->unique()->count(),
            'total_transaksi' => $records->count(),
            'total_nominal' => (string) $records->sum('jumlah_bayar'),
        ];

        return $records;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (array $filters): Collection {
                return $this->buildData(
                    $filters['unit_pos_id']['value'] ?? null,
                    $filters['tanggal']['tanggal_mulai'] ?? null,
                    $filters['tanggal']['tanggal_selesai'] ?? null,
                );
            })
            ->columns([
                TextColumn::make('tanggal_bayar')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('nomor_transaksi')
                    ->label('No. Transaksi')
                    ->searchable(),
                TextColumn::make('tagihanSiswa.siswa.nama_lengkap')
                    ->label('Siswa')
                    ->searchable(),
                TextColumn::make('tagihanSiswa.jenisPembayaran.nama')
                    ->label('Jenis'),
                TextColumn::make('metode_pembayaran')
                    ->label('Metode')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('jumlah_bayar')
                    ->label('Nominal')
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('unit_pos_id')
                    ->label('Unit POS')
                    ->options(UnitPos::query()->where('is_active', true)->pluck('nama', 'id')),
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
            ->emptyStateDescription('Silakan pilih filter untuk melihat transaksi unit POS.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanUnitPosStats::make([
                'summary' => $this->summary,
                'unitPosNama' => $this->unitPosNama,
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
        $unitPosId = $this->getTableFilterState('unit_pos_id')['value'] ?? null;
        $tanggal = $this->getTableFilterState('tanggal') ?? [];
        $tanggalMulai = $tanggal['tanggal_mulai'] ?? null;
        $tanggalSelesai = $tanggal['tanggal_selesai'] ?? null;

        $records = $this->buildData($unitPosId, $tanggalMulai, $tanggalSelesai);
        $summary = $this->summary;

        $no = 1;
        $baris = $records->map(function (Pembayaran $pembayaran) use (&$no): array {
            return [
                (string) $no++,
                $pembayaran->tanggal_bayar?->format('d/m/Y') ?? '-',
                $pembayaran->nomor_transaksi,
                $pembayaran->tagihanSiswa?->siswa?->nama_lengkap ?? '-',
                $pembayaran->tagihanSiswa?->jenisPembayaran?->nama ?? '-',
                ucfirst((string) $pembayaran->metode_pembayaran),
                'Rp '.number_format((float) $pembayaran->jumlah_bayar, 0, ',', '.'),
            ];
        })->toArray();

        $periode = ($this->unitPosNama ? 'Unit POS: '.$this->unitPosNama : 'Semua Unit POS')
            .$this->periodeLabel($tanggalMulai, $tanggalSelesai);

        $pdf = LaporanPdfService::make()
            ->judul('LAPORAN TRANSAKSI UNIT POS')
            ->periode($periode)
            ->kolom([
                'No',
                'Tanggal',
                'No. Transaksi',
                'Siswa',
                'Jenis',
                'Metode',
                ['Nominal', 'right'],
            ])
            ->baris($baris)
            ->ringkasan([
                [
                    '',
                    '',
                    '',
                    '',
                    '',
                    'TOTAL',
                    'Rp '.number_format((float) ($summary['total_nominal'] ?? 0), 0, ',', '.'),
                ],
            ])
            ->landscape()
            ->render();

        $namaFile = LaporanPdfService::make()
            ->namaFile('laporan-unit-pos-'.now()->format('Ymd'));

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $namaFile,
        );
    }

    private function periodeLabel(?string $tanggalMulai, ?string $tanggalSelesai): string
    {
        if (! $tanggalMulai || ! $tanggalSelesai) {
            return '';
        }

        return ' — '.Carbon::parse($tanggalMulai)->translatedFormat('d F Y')
            .' s/d '.Carbon::parse($tanggalSelesai)->translatedFormat('d F Y');
    }
}
