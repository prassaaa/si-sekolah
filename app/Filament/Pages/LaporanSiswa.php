<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanSiswaStats;
use App\Models\Kelas;
use App\Models\Siswa;
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

class LaporanSiswa extends Page implements HasSchemas, HasTable
{
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Laporan Siswa';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'laporan/siswa';

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Siswa';
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
                $kelasId = $filters['kelas_id']['value'] ?? null;

                $query = Siswa::query()
                    ->when($kelasId, fn ($q) => $q->where('kelas_id', $kelasId));

                $totalSiswa = (clone $query)->count();

                $siswaPerJenisKelamin = Siswa::query()
                    ->when($kelasId, fn ($q) => $q->where('kelas_id', $kelasId))
                    ->selectRaw('jenis_kelamin, COUNT(*) as count')
                    ->groupBy('jenis_kelamin')
                    ->pluck('count', 'jenis_kelamin')
                    ->toArray();

                $this->summary = [
                    'total_siswa' => $totalSiswa,
                    'siswa_per_jenis_kelamin' => $siswaPerJenisKelamin,
                ];

                $data = Siswa::query()
                    ->join('kelas', 'siswas.kelas_id', '=', 'kelas.id')
                    ->when($kelasId, fn ($q) => $q->where('kelas_id', $kelasId))
                    ->selectRaw('kelas.nama as kelas_nama, kelas.tingkat, COUNT(siswas.id) as total_siswa, SUM(CASE WHEN siswas.jenis_kelamin = "L" THEN 1 ELSE 0 END) as laki_laki, SUM(CASE WHEN siswas.jenis_kelamin = "P" THEN 1 ELSE 0 END) as perempuan, SUM(CASE WHEN siswas.status = "aktif" THEN 1 ELSE 0 END) as aktif, SUM(CASE WHEN siswas.status != "aktif" THEN 1 ELSE 0 END) as tidak_aktif')
                    ->groupBy('kelas.id', 'kelas.nama', 'kelas.tingkat')
                    ->orderBy('kelas.tingkat')
                    ->orderBy('kelas.nama')
                    ->get();

                return $data->mapWithKeys(fn ($item, $index) => [$index => [
                    'kelas' => $item->kelas_nama,
                    'total_siswa' => $item->total_siswa,
                    'laki_laki' => $item->laki_laki,
                    'perempuan' => $item->perempuan,
                    'aktif' => $item->aktif,
                    'tidak_aktif' => $item->tidak_aktif,
                ]]);
            })
            ->columns([
                TextColumn::make('kelas')
                    ->label('Kelas')
                    ->sortable(),
                TextColumn::make('total_siswa')
                    ->label('Total')
                    ->alignCenter()
                    ->weight('bold'),
                TextColumn::make('laki_laki')
                    ->label('Laki-laki')
                    ->alignCenter()
                    ->color('info'),
                TextColumn::make('perempuan')
                    ->label('Perempuan')
                    ->alignCenter()
                    ->color('pink'),
                TextColumn::make('aktif')
                    ->label('Aktif')
                    ->alignCenter()
                    ->color('success'),
                TextColumn::make('tidak_aktif')
                    ->label('Tidak Aktif')
                    ->alignCenter()
                    ->color('danger'),
            ])
            ->filters([
                SelectFilter::make('kelas_id')
                    ->label('Kelas')
                    ->options(
                        Kelas::query()
                            ->where('is_active', true)
                            ->orderBy('tingkat')
                            ->orderBy('nama')
                            ->pluck('nama', 'id'),
                    ),
            ])
            ->deferFilters(false)
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada data')
            ->emptyStateDescription('Tidak ada data siswa.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanSiswaStats::make([
                'summary' => $this->summary,
            ]),
        ];
    }
}
