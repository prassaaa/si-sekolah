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
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class PerubahanModal extends Page implements HasSchemas, HasTable
{
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;

    protected static \UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Perubahan Modal';

    protected static ?string $navigationLabel = 'Perubahan Modal';

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Perubahan Modal';
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

                if (! $tanggalMulai || ! $tanggalAkhir) {
                    return collect();
                }

                $akunModal = Akun::where('tipe', 'ekuitas')->pluck('id');
                $modalAwal = JurnalUmum::query()
                    ->whereIn('akun_id', $akunModal)
                    ->where('tanggal', '<', $tanggalMulai)
                    ->selectRaw('SUM(kredit) - SUM(debit) as saldo')
                    ->value('saldo') ?? 0;

                $akunPendapatan = Akun::where('tipe', 'pendapatan')->pluck('id');
                $akunBeban = Akun::where('tipe', 'beban')->pluck('id');

                $pendapatan = JurnalUmum::query()
                    ->whereIn('akun_id', $akunPendapatan)
                    ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
                    ->selectRaw('SUM(kredit) - SUM(debit) as total')
                    ->value('total') ?? 0;

                $beban = JurnalUmum::query()
                    ->whereIn('akun_id', $akunBeban)
                    ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
                    ->selectRaw('SUM(debit) - SUM(kredit) as total')
                    ->value('total') ?? 0;

                $labaRugi = $pendapatan - $beban;
                $prive = 0;
                $modalAkhir = $modalAwal + $labaRugi - $prive;

                return collect([
                    0 => ['uraian' => 'Modal Awal', 'nominal' => $modalAwal],
                    1 => ['uraian' => 'Laba/Rugi Periode', 'nominal' => $labaRugi],
                    2 => ['uraian' => 'Prive (Pengambilan)', 'nominal' => $prive],
                    3 => ['uraian' => 'Modal Akhir', 'nominal' => $modalAkhir],
                ]);
            })
            ->columns([
                TextColumn::make('uraian')
                    ->label('Uraian')
                    ->weight('bold'),
                TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->alignEnd()
                    ->weight('bold')
                    ->color(fn (mixed $state): string => $state >= 0 ? 'success' : 'danger'),
            ])
            ->filters([
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
            ->emptyStateDescription('Silakan pilih rentang tanggal untuk melihat perubahan modal.')
            ->emptyStateIcon('heroicon-o-inbox');
    }
}
