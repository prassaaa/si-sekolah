<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanPembayaranStats;
use App\Models\JenisPembayaran;
use App\Models\Semester;
use App\Models\TagihanSiswa;
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

class LaporanPembayaran extends Page implements HasSchemas, HasTable
{
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Laporan Pembayaran';

    protected static ?string $slug = 'laporan/pembayaran';

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Rekap Pembayaran';
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

                $query = TagihanSiswa::query()
                    ->with(['siswa.kelas', 'jenisPembayaran', 'pembayarans'])
                    ->where('semester_id', $semesterId)
                    ->where('status', '!=', 'batal');

                if (filled($filters['jenis_pembayaran_id']['value'] ?? null)) {
                    $query->where('jenis_pembayaran_id', $filters['jenis_pembayaran_id']['value']);
                }

                $tagihans = $query->get();

                $tanggalMulai = $filters['tanggal']['tanggal_mulai'] ?? null;
                $tanggalSelesai = $filters['tanggal']['tanggal_selesai'] ?? null;

                $data = $tagihans->groupBy('jenis_pembayaran_id')->map(function ($items) use ($tanggalMulai, $tanggalSelesai) {
                    $jenis = $items->first()->jenisPembayaran;
                    $pembayarans = $items->flatMap(fn ($t) => $t->pembayarans);

                    if ($tanggalMulai) {
                        $pembayarans = $pembayarans->filter(fn ($p) => $p->tanggal_bayar >= $tanggalMulai);
                    }

                    if ($tanggalSelesai) {
                        $pembayarans = $pembayarans->filter(fn ($p) => $p->tanggal_bayar <= $tanggalSelesai);
                    }

                    return [
                        'jenis' => $jenis?->nama ?? '-',
                        'total_tagihan' => $items->sum('total_tagihan'),
                        'total_terbayar' => $pembayarans->where('status', 'berhasil')->sum('jumlah_bayar'),
                        'total_sisa' => $items->sum('sisa_tagihan'),
                        'jumlah_siswa' => $items->pluck('siswa_id')->unique()->count(),
                        'lunas' => $items->where('status', 'lunas')->count(),
                        'belum_lunas' => $items->whereIn('status', ['belum_bayar', 'sebagian'])->count(),
                    ];
                })->values();

                $this->summary = [
                    'total_tagihan' => $data->sum('total_tagihan'),
                    'total_terbayar' => $data->sum('total_terbayar'),
                    'total_sisa' => $data->sum('total_sisa'),
                    'persentase' => $data->sum('total_tagihan') > 0
                        ? round(($data->sum('total_terbayar') / $data->sum('total_tagihan')) * 100, 1)
                        : 0,
                ];

                return $data;
            })
            ->columns([
                TextColumn::make('jenis')
                    ->label('Jenis Pembayaran'),
                TextColumn::make('jumlah_siswa')
                    ->label('Siswa')
                    ->alignCenter(),
                TextColumn::make('total_tagihan')
                    ->label('Tagihan')
                    ->money('IDR')
                    ->alignEnd(),
                TextColumn::make('total_terbayar')
                    ->label('Terbayar')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('success'),
                TextColumn::make('total_sisa')
                    ->label('Sisa')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('danger'),
                TextColumn::make('lunas')
                    ->label('Lunas')
                    ->alignCenter()
                    ->color('success'),
                TextColumn::make('belum_lunas')
                    ->label('Belum Lunas')
                    ->alignCenter()
                    ->color('danger'),
            ])
            ->filters([
                SelectFilter::make('semester_id')
                    ->label('Semester')
                    ->options(Semester::query()->orderByDesc('tahun_ajaran_id')->pluck('nama', 'id'))
                    ->default($activeSemesterId),
                SelectFilter::make('jenis_pembayaran_id')
                    ->label('Jenis Pembayaran')
                    ->options(JenisPembayaran::query()->where('is_active', true)->pluck('nama', 'id')),
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
                            $indicators[] = 'Dari: '.\Carbon\Carbon::parse($data['tanggal_mulai'])->translatedFormat('d M Y');
                        }

                        if ($data['tanggal_selesai'] ?? null) {
                            $indicators[] = 'Sampai: '.\Carbon\Carbon::parse($data['tanggal_selesai'])->translatedFormat('d M Y');
                        }

                        return $indicators;
                    }),
            ])
            ->deferFilters(false)
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada data')
            ->emptyStateDescription('Silakan pilih semester untuk melihat data pembayaran.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanPembayaranStats::make([
                'summary' => $this->summary,
            ]),
        ];
    }
}
