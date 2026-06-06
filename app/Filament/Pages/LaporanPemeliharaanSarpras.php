<?php

namespace App\Filament\Pages;

use App\Models\SarprasPemeliharaan;
use Carbon\Carbon;
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

class LaporanPemeliharaanSarpras extends Page implements HasSchemas, HasTable
{
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Laporan Pemeliharaan';

    protected static \UnitEnum|string|null $navigationGroup = 'Sarana & Prasarana';

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'sarpras/laporan/pemeliharaan';

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Pemeliharaan Sarana & Prasarana';
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
                $status = $filters['status']['value'] ?? null;
                $jenis = $filters['jenis']['value'] ?? null;
                $startDate = $filters['tanggal']['tanggal_mulai'] ?? null;
                $endDate = $filters['tanggal']['tanggal_akhir'] ?? null;

                $baseQuery = SarprasPemeliharaan::query()
                    ->whereNull('sarpras_pemeliharaans.deleted_at')
                    ->when($status, fn ($q) => $q->where('status', $status))
                    ->when($jenis, fn ($q) => $q->where('jenis', $jenis))
                    ->when($startDate, fn ($q) => $q->whereDate('tanggal', '>=', $startDate))
                    ->when($endDate, fn ($q) => $q->whereDate('tanggal', '<=', $endDate));

                $totalBiaya = (clone $baseQuery)->sum('biaya');
                $totalSelesai = (clone $baseQuery)->where('status', 'selesai')->count();
                $totalProses = (clone $baseQuery)->where('status', 'proses')->count();
                $totalDijadwalkan = (clone $baseQuery)->where('status', 'dijadwalkan')->count();

                $this->summary = [
                    'total_biaya' => $totalBiaya,
                    'total_selesai' => $totalSelesai,
                    'total_proses' => $totalProses,
                    'total_dijadwalkan' => $totalDijadwalkan,
                ];

                $data = (clone $baseQuery)
                    ->selectRaw('
                        jenis,
                        status,
                        COUNT(*) as jumlah,
                        SUM(biaya) as total_biaya
                    ')
                    ->groupBy('jenis', 'status')
                    ->orderBy('jenis')
                    ->orderBy('status')
                    ->get();

                return $data->mapWithKeys(fn ($item, $index) => [$index => [
                    'jenis' => match ($item->jenis) {
                        'rutin' => 'Rutin',
                        'perbaikan' => 'Perbaikan',
                        'kalibrasi' => 'Kalibrasi',
                        default => ucfirst($item->jenis),
                    },
                    'status' => match ($item->status) {
                        'dijadwalkan' => 'Dijadwalkan',
                        'proses' => 'Proses',
                        'selesai' => 'Selesai',
                        'batal' => 'Batal',
                        default => ucfirst($item->status),
                    },
                    'status_raw' => $item->status,
                    'jumlah' => $item->jumlah,
                    'total_biaya' => $item->total_biaya,
                ]]);
            })
            ->columns([
                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Rutin' => 'info',
                        'Perbaikan' => 'warning',
                        'Kalibrasi' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Dijadwalkan' => 'gray',
                        'Proses' => 'warning',
                        'Selesai' => 'success',
                        'Batal' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->alignCenter()
                    ->weight('bold'),
                TextColumn::make('total_biaya')
                    ->label('Total Biaya')
                    ->money('IDR')
                    ->alignEnd()
                    ->weight('bold'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'dijadwalkan' => 'Dijadwalkan',
                        'proses' => 'Proses',
                        'selesai' => 'Selesai',
                        'batal' => 'Batal',
                    ]),
                SelectFilter::make('jenis')
                    ->label('Jenis')
                    ->options([
                        'rutin' => 'Rutin',
                        'perbaikan' => 'Perbaikan',
                        'kalibrasi' => 'Kalibrasi',
                    ]),
                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('tanggal_mulai')
                            ->label('Tanggal Mulai')
                            ->default(now()->startOfMonth()),
                        DatePicker::make('tanggal_akhir')
                            ->label('Tanggal Akhir')
                            ->default(now()->endOfMonth()),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['tanggal_mulai'] ?? null) {
                            $indicators[] = 'Dari: '.Carbon::parse($data['tanggal_mulai'])->translatedFormat('d M Y');
                        }

                        if ($data['tanggal_akhir'] ?? null) {
                            $indicators[] = 'Sampai: '.Carbon::parse($data['tanggal_akhir'])->translatedFormat('d M Y');
                        }

                        return $indicators;
                    }),
            ])
            ->deferFilters(false)
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada data')
            ->emptyStateDescription('Tidak ada data pemeliharaan.')
            ->emptyStateIcon('heroicon-o-inbox');
    }
}
