<?php

namespace App\Filament\Pages;

use App\Models\SarprasBarang;
use App\Models\SarprasKategori;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
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

class LaporanKondisiSarpras extends Page implements HasSchemas, HasTable
{
    use HasPageShield;
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Laporan Kondisi';

    protected static \UnitEnum|string|null $navigationGroup = 'Sarana & Prasarana';

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'sarpras/laporan/kondisi';

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Kondisi Sarana & Prasarana';
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
                $tipe = $filters['tipe']['value'] ?? null;

                $data = SarprasBarang::query()
                    ->join('sarpras_kategoris', 'sarpras_barangs.sarpras_kategori_id', '=', 'sarpras_kategoris.id')
                    ->whereNull('sarpras_barangs.deleted_at')
                    ->when($kategoriId, fn ($q) => $q->where('sarpras_barangs.sarpras_kategori_id', $kategoriId))
                    ->when($tipe, fn ($q) => $q->where('sarpras_barangs.tipe', $tipe))
                    ->selectRaw('
                        sarpras_kategoris.id as kategori_id,
                        sarpras_kategoris.nama as kategori_nama,
                        COUNT(sarpras_barangs.id) as total,
                        SUM(CASE WHEN sarpras_barangs.kondisi = \'baik\' THEN 1 ELSE 0 END) as baik,
                        SUM(CASE WHEN sarpras_barangs.kondisi = \'rusak_ringan\' THEN 1 ELSE 0 END) as rusak_ringan,
                        SUM(CASE WHEN sarpras_barangs.kondisi = \'rusak_berat\' THEN 1 ELSE 0 END) as rusak_berat
                    ')
                    ->groupBy('sarpras_kategoris.id', 'sarpras_kategoris.nama')
                    ->orderBy('sarpras_kategoris.nama')
                    ->get();

                $totalBarang = $data->sum('total');
                $totalBaik = $data->sum('baik');
                $totalRusakRingan = $data->sum('rusak_ringan');
                $totalRusakBerat = $data->sum('rusak_berat');

                $this->summary = [
                    'total_barang' => $totalBarang,
                    'total_baik' => $totalBaik,
                    'total_rusak_ringan' => $totalRusakRingan,
                    'total_rusak_berat' => $totalRusakBerat,
                ];

                return $data->mapWithKeys(fn ($item, $index) => [$index => [
                    'kategori' => $item->kategori_nama,
                    'total' => $item->total,
                    'baik' => $item->baik,
                    'rusak_ringan' => $item->rusak_ringan,
                    'rusak_berat' => $item->rusak_berat,
                    'persen_baik' => $item->total > 0
                        ? number_format($item->baik / $item->total * 100, 1).'%'
                        : '0%',
                ]]);
            })
            ->columns([
                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->alignCenter()
                    ->weight('bold'),
                TextColumn::make('baik')
                    ->label('Baik')
                    ->alignCenter()
                    ->color('success'),
                TextColumn::make('rusak_ringan')
                    ->label('Rusak Ringan')
                    ->alignCenter()
                    ->color('warning'),
                TextColumn::make('rusak_berat')
                    ->label('Rusak Berat')
                    ->alignCenter()
                    ->color('danger'),
                TextColumn::make('persen_baik')
                    ->label('% Baik')
                    ->alignCenter(),
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
                SelectFilter::make('tipe')
                    ->label('Tipe')
                    ->options([
                        'aset' => 'Aset',
                        'bahan' => 'Bahan',
                    ]),
            ])
            ->deferFilters(false)
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada data')
            ->emptyStateDescription('Tidak ada data kondisi barang.')
            ->emptyStateIcon('heroicon-o-inbox');
    }
}
