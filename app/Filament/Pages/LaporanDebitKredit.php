<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanDebitKreditStats;
use App\Models\KasKeluar;
use App\Models\KasMasuk;
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

class LaporanDebitKredit extends Page implements HasSchemas, HasTable
{
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 8;

    protected static ?string $title = 'Laporan Debit Kredit';

    protected static ?string $slug = 'laporan/debit-kredit';

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Debit & Kredit (Kas)';
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
                $jenis = $filters['jenis']['value'] ?? null;

                if (! $tanggalMulai || ! $tanggalSelesai) {
                    $this->summary = [];

                    return collect();
                }

                $data = collect();

                if (! $jenis || $jenis === 'masuk') {
                    $kasMasuk = KasMasuk::query()
                        ->with('akun')
                        ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
                        ->orderBy('tanggal')
                        ->get()
                        ->map(fn ($k) => [
                            'tanggal' => $k->tanggal->format('Y-m-d'),
                            'nomor_bukti' => $k->nomor_bukti,
                            'akun' => $k->akun?->nama ?? '-',
                            'keterangan' => $k->sumber ?? $k->keterangan ?? '-',
                            'jenis' => 'Kas Masuk',
                            'nominal' => $k->nominal,
                        ]);
                    $data = $data->merge($kasMasuk);
                }

                if (! $jenis || $jenis === 'keluar') {
                    $kasKeluar = KasKeluar::query()
                        ->with('akun')
                        ->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
                        ->orderBy('tanggal')
                        ->get()
                        ->map(fn ($k) => [
                            'tanggal' => $k->tanggal->format('Y-m-d'),
                            'nomor_bukti' => $k->nomor_bukti,
                            'akun' => $k->akun?->nama ?? '-',
                            'keterangan' => $k->penerima ?? $k->keterangan ?? '-',
                            'jenis' => 'Kas Keluar',
                            'nominal' => $k->nominal,
                        ]);
                    $data = $data->merge($kasKeluar);
                }

                $data = $data->sortBy('tanggal')->values();

                $totalMasuk = $data->where('jenis', 'Kas Masuk')->sum('nominal');
                $totalKeluar = $data->where('jenis', 'Kas Keluar')->sum('nominal');

                $this->summary = [
                    'total_masuk' => $totalMasuk,
                    'total_keluar' => $totalKeluar,
                    'selisih' => $totalMasuk - $totalKeluar,
                    'jml_masuk' => $data->where('jenis', 'Kas Masuk')->count(),
                    'jml_keluar' => $data->where('jenis', 'Kas Keluar')->count(),
                ];

                return $data;
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
                        'Kas Masuk' => 'success',
                        'Kas Keluar' => 'danger',
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
                        'masuk' => 'Kas Masuk (Debit)',
                        'keluar' => 'Kas Keluar (Kredit)',
                    ]),
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
            ->emptyStateDescription('Silakan pilih rentang tanggal untuk melihat data kas.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanDebitKreditStats::make([
                'summary' => $this->summary,
            ]),
        ];
    }
}
