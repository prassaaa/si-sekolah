<?php

namespace App\Filament\Pages;

use App\Models\Akun;
use App\Models\JurnalUmum;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class LabaRugi extends Page implements HasSchemas, HasTable
{
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static \UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Laporan Laba Rugi';

    protected static ?string $navigationLabel = 'Laba Rugi';

    public float $totalPendapatan = 0;

    public float $totalBeban = 0;

    public float $labaRugi = 0;

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Laba Rugi';
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
                $tanggalAkhir = $filters['tanggal']['tanggal_akhir'] ?? null;
                $kategori = $filters['kategori']['value'] ?? null;

                if (! $tanggalMulai || ! $tanggalAkhir) {
                    $this->totalPendapatan = 0;
                    $this->totalBeban = 0;
                    $this->labaRugi = 0;

                    return collect();
                }

                $data = collect();

                if (! $kategori || $kategori === 'pendapatan') {
                    $akunPendapatan = Akun::where('tipe', 'pendapatan')->pluck('id');
                    $pendapatan = JurnalUmum::query()
                        ->whereIn('akun_id', $akunPendapatan)
                        ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
                        ->with('akun')
                        ->get()
                        ->groupBy('akun_id')
                        ->map(fn ($items) => [
                            'akun' => $items->first()->akun?->nama ?? '-',
                            'kategori' => 'Pendapatan',
                            'nominal' => $items->sum('kredit') - $items->sum('debit'),
                        ])->values();
                    $data = $data->merge($pendapatan);
                }

                if (! $kategori || $kategori === 'beban') {
                    $akunBeban = Akun::where('tipe', 'beban')->pluck('id');
                    $beban = JurnalUmum::query()
                        ->whereIn('akun_id', $akunBeban)
                        ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
                        ->with('akun')
                        ->get()
                        ->groupBy('akun_id')
                        ->map(fn ($items) => [
                            'akun' => $items->first()->akun?->nama ?? '-',
                            'kategori' => 'Beban',
                            'nominal' => $items->sum('debit') - $items->sum('kredit'),
                        ])->values();
                    $data = $data->merge($beban);
                }

                $this->totalPendapatan = $data->where('kategori', 'Pendapatan')->sum('nominal');
                $this->totalBeban = $data->where('kategori', 'Beban')->sum('nominal');
                $this->labaRugi = $this->totalPendapatan - $this->totalBeban;

                return $data->values();
            })
            ->columns([
                TextColumn::make('akun')
                    ->label('Akun'),
                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pendapatan' => 'success',
                        'Beban' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->alignEnd()
                    ->weight('bold'),
            ])
            ->filters([
                SelectFilter::make('kategori')
                    ->label('Kategori')
                    ->options([
                        'pendapatan' => 'Pendapatan',
                        'beban' => 'Beban',
                    ]),
                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('tanggal_mulai')
                            ->label('Tanggal Mulai')
                            ->default(now()->startOfMonth()),
                        DatePicker::make('tanggal_akhir')
                            ->label('Tanggal Akhir')
                            ->default(now()),
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
            ->emptyStateDescription('Silakan pilih rentang tanggal untuk melihat laporan laba rugi.')
            ->emptyStateIcon('heroicon-o-inbox');
    }
}
