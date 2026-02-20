<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanTahfidzStats;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\Tahfidz;
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

class LaporanTahfidz extends Page implements HasSchemas, HasTable
{
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Laporan Tahfidz';

    protected static ?string $slug = 'laporan/tahfidz';

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Rekap Tahfidz';
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
        $activeSemesterId = Semester::query()->where('is_active', true)->value('id');

        return $table
            ->records(function (array $filters) use ($activeSemesterId): Collection {
                $semesterId = $filters['semester_id']['value'] ?? $activeSemesterId;

                if (! $semesterId) {
                    $this->summary = [];

                    return collect();
                }

                $query = Tahfidz::query()
                    ->with(['siswa.kelas', 'penguji'])
                    ->where('semester_id', $semesterId);

                if (filled($filters['kelas']['value'] ?? null)) {
                    $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $filters['kelas']['value']));
                }

                $tahfidzs = $query->get();

                $data = $tahfidzs->groupBy('siswa_id')->map(function ($items) {
                    $siswa = $items->first()->siswa;

                    return [
                        'siswa' => $siswa?->nama_lengkap ?? '-',
                        'kelas' => $siswa?->kelas?->nama ?? '-',
                        'total_setoran' => $items->where('jenis', 'setoran')->count(),
                        'total_murojaah' => $items->where('jenis', 'murojaah')->count(),
                        'total_ayat' => $items->sum('jumlah_ayat'),
                        'rata_rata_nilai' => round($items->avg('nilai'), 1),
                        'lulus' => $items->where('status', 'lulus')->count(),
                        'belum_lulus' => $items->where('status', 'belum_lulus')->count(),
                    ];
                })->sortBy('kelas')->values();

                $this->summary = [
                    'total_siswa' => $data->count(),
                    'total_setoran' => $data->sum('total_setoran'),
                    'total_murojaah' => $data->sum('total_murojaah'),
                    'total_ayat' => $data->sum('total_ayat'),
                    'rata_rata_nilai' => round($data->avg('rata_rata_nilai'), 1),
                ];

                return $data;
            })
            ->columns([
                TextColumn::make('siswa')
                    ->label('Nama Siswa')
                    ->searchable(),
                TextColumn::make('kelas')
                    ->label('Kelas')
                    ->sortable(),
                TextColumn::make('total_setoran')
                    ->label('Setoran')
                    ->alignCenter(),
                TextColumn::make('total_murojaah')
                    ->label("Muroja'ah")
                    ->alignCenter(),
                TextColumn::make('total_ayat')
                    ->label('Total Ayat')
                    ->alignCenter(),
                TextColumn::make('rata_rata_nilai')
                    ->label('Rata-rata Nilai')
                    ->alignCenter(),
                TextColumn::make('lulus')
                    ->label('Lulus')
                    ->alignCenter()
                    ->color('success'),
                TextColumn::make('belum_lulus')
                    ->label('Belum Lulus')
                    ->alignCenter()
                    ->color('danger'),
            ])
            ->filters([
                SelectFilter::make('semester_id')
                    ->label('Semester')
                    ->options(Semester::query()->orderByDesc('tahun_ajaran_id')->pluck('nama', 'id'))
                    ->default($activeSemesterId),
                SelectFilter::make('kelas')
                    ->label('Kelas')
                    ->options(Kelas::query()->where('is_active', true)->orderBy('tingkat')->orderBy('nama')->pluck('nama', 'id')),
            ])
            ->deferFilters(false)
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada data')
            ->emptyStateDescription('Silakan pilih semester untuk melihat data tahfidz.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanTahfidzStats::make([
                'summary' => $this->summary,
            ]),
        ];
    }
}
