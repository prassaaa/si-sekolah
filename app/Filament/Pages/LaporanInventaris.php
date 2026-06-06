<?php

namespace App\Filament\Pages;

use App\Models\Ruangan;
use App\Models\SarprasBarang;
use App\Models\SarprasKategori;
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

class LaporanInventaris extends Page implements HasSchemas, HasTable
{
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Laporan Inventaris';

    protected static \UnitEnum|string|null $navigationGroup = 'Sarana & Prasarana';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'sarpras/laporan/inventaris';

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Inventaris';
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
                $ruanganId = $filters['ruangan_id']['value'] ?? null;
                $kondisi = $filters['kondisi']['value'] ?? null;
                $status = $filters['status']['value'] ?? null;
                $tipe = $filters['tipe']['value'] ?? null;

                $data = SarprasBarang::query()
                    ->join('sarpras_kategoris', 'sarpras_barangs.sarpras_kategori_id', '=', 'sarpras_kategoris.id')
                    ->leftJoin('ruangans', 'sarpras_barangs.ruangan_id', '=', 'ruangans.id')
                    ->whereNull('sarpras_barangs.deleted_at')
                    ->when($kategoriId, fn ($q) => $q->where('sarpras_barangs.sarpras_kategori_id', $kategoriId))
                    ->when($ruanganId, fn ($q) => $q->where('sarpras_barangs.ruangan_id', $ruanganId))
                    ->when($kondisi, fn ($q) => $q->where('sarpras_barangs.kondisi', $kondisi))
                    ->when($status, fn ($q) => $q->where('sarpras_barangs.status', $status))
                    ->when($tipe, fn ($q) => $q->where('sarpras_barangs.tipe', $tipe))
                    ->selectRaw('
                        sarpras_kategoris.nama as kategori_nama,
                        COUNT(sarpras_barangs.id) as jumlah_unit,
                        SUM(sarpras_barangs.harga_perolehan) as total_nilai,
                        SUM(CASE WHEN sarpras_barangs.kondisi = \'baik\' THEN 1 ELSE 0 END) as kondisi_baik,
                        SUM(CASE WHEN sarpras_barangs.kondisi = \'rusak_ringan\' THEN 1 ELSE 0 END) as kondisi_rusak_ringan,
                        SUM(CASE WHEN sarpras_barangs.kondisi = \'rusak_berat\' THEN 1 ELSE 0 END) as kondisi_rusak_berat
                    ')
                    ->groupBy('sarpras_kategoris.id', 'sarpras_kategoris.nama')
                    ->orderBy('sarpras_kategoris.nama')
                    ->get();

                $totalUnit = $data->sum('jumlah_unit');
                $totalNilai = $data->sum('total_nilai');

                $this->summary = [
                    'total_unit' => $totalUnit,
                    'total_nilai' => $totalNilai,
                ];

                return $data->mapWithKeys(fn ($item, $index) => [$index => [
                    'kategori' => $item->kategori_nama,
                    'jumlah_unit' => $item->jumlah_unit,
                    'total_nilai' => $item->total_nilai,
                    'kondisi_baik' => $item->kondisi_baik,
                    'kondisi_rusak_ringan' => $item->kondisi_rusak_ringan,
                    'kondisi_rusak_berat' => $item->kondisi_rusak_berat,
                ]]);
            })
            ->columns([
                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->sortable(),
                TextColumn::make('jumlah_unit')
                    ->label('Jumlah Unit')
                    ->alignCenter()
                    ->weight('bold'),
                TextColumn::make('total_nilai')
                    ->label('Total Nilai')
                    ->money('IDR')
                    ->alignEnd()
                    ->weight('bold'),
                TextColumn::make('kondisi_baik')
                    ->label('Baik')
                    ->alignCenter()
                    ->color('success'),
                TextColumn::make('kondisi_rusak_ringan')
                    ->label('Rusak Ringan')
                    ->alignCenter()
                    ->color('warning'),
                TextColumn::make('kondisi_rusak_berat')
                    ->label('Rusak Berat')
                    ->alignCenter()
                    ->color('danger'),
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
                SelectFilter::make('ruangan_id')
                    ->label('Ruangan')
                    ->options(
                        Ruangan::query()
                            ->where('is_active', true)
                            ->orderBy('nama')
                            ->pluck('nama', 'id'),
                    ),
                SelectFilter::make('kondisi')
                    ->label('Kondisi')
                    ->options([
                        'baik' => 'Baik',
                        'rusak_ringan' => 'Rusak Ringan',
                        'rusak_berat' => 'Rusak Berat',
                    ]),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'tersedia' => 'Tersedia',
                        'dipinjam' => 'Dipinjam',
                        'perbaikan' => 'Perbaikan',
                        'dihapus' => 'Dihapus',
                    ]),
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
            ->emptyStateDescription('Tidak ada data inventaris.')
            ->emptyStateIcon('heroicon-o-inbox');
    }
}
