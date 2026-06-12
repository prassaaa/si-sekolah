<?php

namespace App\Filament\Pages;

use App\Models\SarprasPeminjaman;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
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

class LaporanPeminjamanSarpras extends Page implements HasSchemas, HasTable
{
    use HasPageShield;
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Laporan Peminjaman';

    protected static \UnitEnum|string|null $navigationGroup = 'Sarana & Prasarana';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'sarpras/laporan/peminjaman';

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Peminjaman Sarana & Prasarana';
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
                $startDate = $filters['tanggal']['tanggal_mulai'] ?? null;
                $endDate = $filters['tanggal']['tanggal_akhir'] ?? null;

                // All-time summary counts (no date/status filter) — shows overall state
                $summaryQuery = SarprasPeminjaman::query()
                    ->when($startDate, fn ($q) => $q->whereDate('tanggal_pinjam', '>=', $startDate))
                    ->when($endDate, fn ($q) => $q->whereDate('tanggal_pinjam', '<=', $endDate));

                $totalDipinjam = (clone $summaryQuery)->where('status', 'dipinjam')->count();
                $totalDikembalikan = (clone $summaryQuery)->where('status', 'dikembalikan')->count();
                $totalTerlambat = (clone $summaryQuery)->where('status', 'terlambat')->count();
                $totalHilang = (clone $summaryQuery)->where('status', 'hilang')->count();

                $this->summary = [
                    'total_dipinjam' => $totalDipinjam,
                    'total_dikembalikan' => $totalDikembalikan,
                    'total_terlambat' => $totalTerlambat,
                    'total_hilang' => $totalHilang,
                ];

                // Status-filtered query for the table rows
                $baseQuery = SarprasPeminjaman::query()
                    ->join('sarpras_barangs', 'sarpras_peminjamans.sarpras_barang_id', '=', 'sarpras_barangs.id')
                    ->whereNull('sarpras_peminjamans.deleted_at')
                    ->when($status, fn ($q) => $q->where('sarpras_peminjamans.status', $status))
                    ->when($startDate, fn ($q) => $q->whereDate('sarpras_peminjamans.tanggal_pinjam', '>=', $startDate))
                    ->when($endDate, fn ($q) => $q->whereDate('sarpras_peminjamans.tanggal_pinjam', '<=', $endDate));

                $data = $baseQuery
                    ->selectRaw('
                        sarpras_peminjamans.status,
                        COUNT(*) as jumlah
                    ')
                    ->groupBy('sarpras_peminjamans.status')
                    ->orderBy('sarpras_peminjamans.status')
                    ->get();

                return $data->mapWithKeys(fn ($item, $index) => [$index => [
                    'status' => match ($item->status) {
                        'dipinjam' => 'Dipinjam',
                        'dikembalikan' => 'Dikembalikan',
                        'terlambat' => 'Terlambat',
                        'hilang' => 'Hilang',
                        default => ucfirst($item->status),
                    },
                    'status_raw' => $item->status,
                    'jumlah' => $item->jumlah,
                ]]);
            })
            ->columns([
                TextColumn::make('status')
                    ->label('Status Peminjaman')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Dipinjam' => 'warning',
                        'Dikembalikan' => 'success',
                        'Terlambat' => 'danger',
                        'Hilang' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->alignCenter()
                    ->weight('bold'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'dipinjam' => 'Dipinjam',
                        'dikembalikan' => 'Dikembalikan',
                        'terlambat' => 'Terlambat',
                        'hilang' => 'Hilang',
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
            ->emptyStateDescription('Tidak ada data peminjaman.')
            ->emptyStateIcon('heroicon-o-inbox');
    }
}
