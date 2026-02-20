<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanPembayaranPerTanggalStats;
use App\Models\Pembayaran;
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
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class LaporanPembayaranPerTanggal extends Page implements HasSchemas, HasTable
{
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Pembayaran Per Tanggal';

    protected static ?string $slug = 'laporan/pembayaran-per-tanggal';

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Pembayaran Per Tanggal';
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
                $tanggalMulai = $filters['tanggal']['tanggal_mulai'] ?? null;
                $tanggalSelesai = $filters['tanggal']['tanggal_selesai'] ?? null;

                if (! $tanggalMulai || ! $tanggalSelesai) {
                    $this->summary = [];

                    return collect();
                }

                $pembayarans = Pembayaran::query()
                    ->with(['tagihanSiswa.siswa.kelas', 'tagihanSiswa.jenisPembayaran', 'penerima'])
                    ->where('status', 'berhasil')
                    ->whereBetween('tanggal_bayar', [$tanggalMulai, $tanggalSelesai])
                    ->orderBy('tanggal_bayar')
                    ->get();

                $data = $pembayarans->groupBy(fn ($p) => $p->tanggal_bayar->format('Y-m-d'))->map(function ($items, $date) {
                    return [
                        'tanggal' => $date,
                        'jumlah_transaksi' => $items->count(),
                        'tunai' => $items->where('metode_pembayaran', 'tunai')->sum('jumlah_bayar'),
                        'transfer' => $items->where('metode_pembayaran', 'transfer')->sum('jumlah_bayar'),
                        'lainnya' => $items->whereNotIn('metode_pembayaran', ['tunai', 'transfer'])->sum('jumlah_bayar'),
                        'total' => $items->sum('jumlah_bayar'),
                    ];
                })->values();

                $this->summary = [
                    'total_transaksi' => $pembayarans->count(),
                    'total_tunai' => $pembayarans->where('metode_pembayaran', 'tunai')->sum('jumlah_bayar'),
                    'total_transfer' => $pembayarans->where('metode_pembayaran', 'transfer')->sum('jumlah_bayar'),
                    'total_lainnya' => $pembayarans->whereNotIn('metode_pembayaran', ['tunai', 'transfer'])->sum('jumlah_bayar'),
                    'grand_total' => $pembayarans->sum('jumlah_bayar'),
                ];

                return $data;
            })
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('jumlah_transaksi')
                    ->label('Transaksi')
                    ->alignCenter(),
                TextColumn::make('tunai')
                    ->label('Tunai')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('success'),
                TextColumn::make('transfer')
                    ->label('Transfer')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('info'),
                TextColumn::make('lainnya')
                    ->label('Lainnya')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('warning'),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->alignEnd()
                    ->weight('bold'),
            ])
            ->filters([
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
            ->emptyStateDescription('Silakan pilih rentang tanggal untuk melihat data pembayaran.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanPembayaranPerTanggalStats::make([
                'summary' => $this->summary,
            ]),
        ];
    }
}
