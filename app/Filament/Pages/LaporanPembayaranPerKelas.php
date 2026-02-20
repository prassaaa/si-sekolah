<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanPembayaranPerKelasStats;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\TagihanSiswa;
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

class LaporanPembayaranPerKelas extends Page implements HasSchemas, HasTable
{
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Pembayaran Per Kelas';

    protected static ?string $slug = 'laporan/pembayaran-per-kelas';

    public array $summary = [];

    public ?string $kelasNama = null;

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Pembayaran Per Kelas';
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
                $kelasId = $filters['kelas_id']['value'] ?? null;

                if (! $semesterId || ! $kelasId) {
                    $this->summary = [];
                    $this->kelasNama = null;

                    return collect();
                }

                $this->kelasNama = Kelas::query()->find($kelasId)?->nama;

                $tagihans = TagihanSiswa::query()
                    ->with(['siswa', 'jenisPembayaran', 'pembayarans'])
                    ->where('semester_id', $semesterId)
                    ->whereHas('siswa', fn ($q) => $q->where('kelas_id', $kelasId))
                    ->get();

                $data = $tagihans->groupBy('siswa_id')->map(function ($items) {
                    $siswa = $items->first()->siswa;

                    return [
                        'nis' => $siswa?->nis ?? '-',
                        'nama' => $siswa?->nama_lengkap ?? '-',
                        'total_tagihan' => $items->sum('total_tagihan'),
                        'total_terbayar' => $items->sum('total_terbayar'),
                        'sisa' => $items->sum('sisa_tagihan'),
                        'status' => $items->every(fn ($t) => $t->status === 'lunas') ? 'Lunas' : 'Belum Lunas',
                    ];
                })->sortBy('nama')->values();

                $this->summary = [
                    'total_siswa' => $data->count(),
                    'total_tagihan' => $data->sum('total_tagihan'),
                    'total_terbayar' => $data->sum('total_terbayar'),
                    'total_sisa' => $data->sum('sisa'),
                    'lunas' => $data->where('status', 'Lunas')->count(),
                    'belum_lunas' => $data->where('status', 'Belum Lunas')->count(),
                ];

                return $data;
            })
            ->columns([
                TextColumn::make('nis')
                    ->label('NIS')
                    ->searchable(),
                TextColumn::make('nama')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_tagihan')
                    ->label('Tagihan')
                    ->money('IDR')
                    ->alignEnd(),
                TextColumn::make('total_terbayar')
                    ->label('Terbayar')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('success'),
                TextColumn::make('sisa')
                    ->label('Sisa')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('danger'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Lunas' => 'success',
                        default => 'danger',
                    })
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('semester_id')
                    ->label('Semester')
                    ->options(Semester::query()->orderByDesc('tahun_ajaran_id')->pluck('nama', 'id'))
                    ->default($activeSemesterId),
                SelectFilter::make('kelas_id')
                    ->label('Kelas')
                    ->options(Kelas::query()->where('is_active', true)->orderBy('tingkat')->orderBy('nama')->pluck('nama', 'id')),
            ])
            ->deferFilters(false)
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada data')
            ->emptyStateDescription('Silakan pilih semester dan kelas untuk melihat data pembayaran.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanPembayaranPerKelasStats::make([
                'summary' => $this->summary,
                'kelasNama' => $this->kelasNama,
            ]),
        ];
    }
}
