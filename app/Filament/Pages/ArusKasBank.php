<?php

namespace App\Filament\Pages;

use App\Models\KasKeluar;
use App\Models\KasMasuk;
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

class ArusKasBank extends Page implements HasSchemas, HasTable
{
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static \UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Arus Kas/Bank';

    protected static ?string $navigationLabel = 'Arus Kas/Bank';

    public function getTitle(): string|Htmlable
    {
        return 'Arus Kas/Bank';
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
                $jenis = $filters['jenis']['value'] ?? null;

                if (! $tanggalMulai || ! $tanggalAkhir) {
                    return collect();
                }

                $data = collect();

                if (! $jenis || $jenis === 'masuk') {
                    $kasMasuk = KasMasuk::query()
                        ->with('akun')
                        ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
                        ->orderBy('tanggal')
                        ->get()
                        ->map(fn ($item) => [
                            'tanggal' => $item->tanggal->format('Y-m-d'),
                            'nomor_bukti' => $item->nomor_bukti,
                            'akun' => $item->akun?->nama ?? '-',
                            'keterangan' => $item->sumber ?? '-',
                            'jenis' => 'Masuk',
                            'nominal' => $item->nominal,
                        ]);
                    $data = $data->merge($kasMasuk);
                }

                if (! $jenis || $jenis === 'keluar') {
                    $kasKeluar = KasKeluar::query()
                        ->with('akun')
                        ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
                        ->orderBy('tanggal')
                        ->get()
                        ->map(fn ($item) => [
                            'tanggal' => $item->tanggal->format('Y-m-d'),
                            'nomor_bukti' => $item->nomor_bukti,
                            'akun' => $item->akun?->nama ?? '-',
                            'keterangan' => $item->penerima ?? '-',
                            'jenis' => 'Keluar',
                            'nominal' => $item->nominal,
                        ]);
                    $data = $data->merge($kasKeluar);
                }

                return $data->sortBy('tanggal')->values();
            })
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('nomor_bukti')
                    ->label('No. Bukti')
                    ->searchable(),
                TextColumn::make('akun')
                    ->label('Akun'),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(40),
                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Masuk' => 'success',
                        'Keluar' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->alignEnd()
                    ->weight('bold'),
            ])
            ->filters([
                SelectFilter::make('jenis')
                    ->label('Jenis')
                    ->options([
                        'masuk' => 'Kas Masuk',
                        'keluar' => 'Kas Keluar',
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
            ->emptyStateDescription('Silakan pilih rentang tanggal untuk melihat arus kas.')
            ->emptyStateIcon('heroicon-o-inbox');
    }
}
