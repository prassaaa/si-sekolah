<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanKeuanganStats;
use App\Models\Pembayaran;
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
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class LaporanKeuangan extends Page implements HasSchemas, HasTable
{
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Laporan Keuangan';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'laporan/keuangan';

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Keuangan';
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
                $startDate = $filters['tanggal']['tanggal_mulai'] ?? null;
                $endDate = $filters['tanggal']['tanggal_akhir'] ?? null;

                $totalTagihan = TagihanSiswa::where('status', '!=', 'batal')
                    ->when($startDate, fn ($q) => $q->whereDate('tanggal_tagihan', '>=', $startDate))
                    ->when($endDate, fn ($q) => $q->whereDate('tanggal_tagihan', '<=', $endDate))
                    ->sum('total_tagihan');

                $totalPembayaran = Pembayaran::where('status', 'berhasil')
                    ->when($startDate, fn ($q) => $q->whereDate('tanggal_bayar', '>=', $startDate))
                    ->when($endDate, fn ($q) => $q->whereDate('tanggal_bayar', '<=', $endDate))
                    ->sum('jumlah_bayar');

                $tagihanLunas = TagihanSiswa::where('status', 'lunas')
                    ->when($startDate, fn ($q) => $q->whereDate('tanggal_tagihan', '>=', $startDate))
                    ->when($endDate, fn ($q) => $q->whereDate('tanggal_tagihan', '<=', $endDate))
                    ->count();

                $tagihanBelumLunas = TagihanSiswa::whereIn('status', ['belum_bayar', 'sebagian'])
                    ->when($startDate, fn ($q) => $q->whereDate('tanggal_tagihan', '>=', $startDate))
                    ->when($endDate, fn ($q) => $q->whereDate('tanggal_tagihan', '<=', $endDate))
                    ->count();

                $pembayaranPerMetode = Pembayaran::where('status', 'berhasil')
                    ->when($startDate, fn ($q) => $q->whereDate('tanggal_bayar', '>=', $startDate))
                    ->when($endDate, fn ($q) => $q->whereDate('tanggal_bayar', '<=', $endDate))
                    ->selectRaw('metode_pembayaran, SUM(jumlah_bayar) as total, COUNT(*) as jumlah')
                    ->groupBy('metode_pembayaran')
                    ->get();

                $this->summary = [
                    'total_tagihan' => $totalTagihan,
                    'total_pembayaran' => $totalPembayaran,
                    'tagihan_lunas' => $tagihanLunas,
                    'tagihan_belum_lunas' => $tagihanBelumLunas,
                ];

                return $pembayaranPerMetode->mapWithKeys(function ($item, $index) {
                    return [$index => [
                        'metode' => match ($item->metode_pembayaran) {
                            'tunai' => 'Tunai',
                            'transfer' => 'Transfer Bank',
                            'qris' => 'QRIS',
                            'virtual_account' => 'Virtual Account',
                            default => ucfirst($item->metode_pembayaran),
                        },
                        'jumlah' => $item->jumlah,
                        'total' => $item->total,
                    ]];
                });
            })
            ->columns([
                TextColumn::make('metode')
                    ->label('Metode Pembayaran')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Tunai' => 'success',
                        'Transfer Bank' => 'info',
                        'QRIS' => 'primary',
                        'Virtual Account' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('jumlah')
                    ->label('Jumlah Transaksi')
                    ->alignCenter(),
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
                            ->label('Tanggal Mulai')
                            ->default(now()->startOfMonth()),
                        DatePicker::make('tanggal_akhir')
                            ->label('Tanggal Akhir')
                            ->default(now()->endOfMonth()),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['tanggal_mulai'] ?? null) {
                            $indicators[] = 'Dari: '.\Carbon\Carbon::parse($data['tanggal_mulai'])->translatedFormat('d M Y');
                        }

                        if ($data['tanggal_akhir'] ?? null) {
                            $indicators[] = 'Sampai: '.\Carbon\Carbon::parse($data['tanggal_akhir'])->translatedFormat('d M Y');
                        }

                        return $indicators;
                    }),
            ])
            ->deferFilters(false)
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada data')
            ->emptyStateDescription('Silakan pilih periode untuk melihat laporan keuangan.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanKeuanganStats::make([
                'summary' => $this->summary,
            ]),
        ];
    }
}
