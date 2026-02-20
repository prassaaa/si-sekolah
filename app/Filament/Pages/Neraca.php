<?php

namespace App\Filament\Pages;

use App\Models\Akun;
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
use Illuminate\Support\Facades\DB;

class Neraca extends Page implements HasSchemas, HasTable
{
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static \UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Neraca';

    protected static ?string $navigationLabel = 'Neraca';

    public function getTitle(): string|Htmlable
    {
        return 'Neraca';
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
                $tanggal = $filters['tanggal']['tanggal'] ?? null;
                $tipe = $filters['tipe']['value'] ?? null;

                if (! $tanggal) {
                    return collect();
                }

                $saldoPerAkun = $this->calculateSaldoPerAkun($tanggal);

                $akuns = Akun::whereIn('tipe', ['aset', 'liabilitas', 'ekuitas'])
                    ->when($tipe, fn ($q) => $q->where('tipe', $tipe))
                    ->get();

                return $akuns->map(function ($akun) use ($saldoPerAkun) {
                    $saldo = $saldoPerAkun[$akun->id] ?? 0;

                    if ($saldo == 0) {
                        return null;
                    }

                    return [
                        'akun' => $akun->nama,
                        'tipe' => match ($akun->tipe) {
                            'aset' => 'Aset',
                            'liabilitas' => 'Kewajiban',
                            'ekuitas' => 'Modal',
                            default => ucfirst($akun->tipe),
                        },
                        'saldo' => $saldo,
                    ];
                })->filter()->values();
            })
            ->columns([
                TextColumn::make('akun')
                    ->label('Akun'),
                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aset' => 'success',
                        'Kewajiban' => 'danger',
                        'Modal' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('saldo')
                    ->label('Saldo')
                    ->money('IDR')
                    ->alignEnd()
                    ->weight('bold'),
            ])
            ->filters([
                SelectFilter::make('tipe')
                    ->label('Tipe')
                    ->options([
                        'aset' => 'Aset',
                        'liabilitas' => 'Kewajiban',
                        'ekuitas' => 'Modal',
                    ]),
                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('tanggal')
                            ->label('Per Tanggal')
                            ->default(now()),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (! ($data['tanggal'] ?? null)) {
                            return null;
                        }

                        return 'Per: '.\Carbon\Carbon::parse($data['tanggal'])->translatedFormat('d M Y');
                    }),
            ])
            ->deferFilters(false)
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada data')
            ->emptyStateDescription('Silakan pilih tanggal untuk melihat neraca.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    /**
     * @return array<int, float>
     */
    private function calculateSaldoPerAkun(string $tanggal): array
    {
        $saldoAwal = DB::table('saldo_awals')
            ->select('akun_id', DB::raw('SUM(saldo) as total'))
            ->where('tanggal', '<=', $tanggal)
            ->whereNull('deleted_at')
            ->groupBy('akun_id')
            ->pluck('total', 'akun_id')
            ->toArray();

        $jurnalDebit = DB::table('jurnal_umums')
            ->select(
                'akun_id',
                DB::raw(
                    'SUM(debit) as total_debit, SUM(kredit) as total_kredit',
                ),
            )
            ->where('tanggal', '<=', $tanggal)
            ->whereNull('deleted_at')
            ->groupBy('akun_id')
            ->get()
            ->keyBy('akun_id');

        $akuns = Akun::whereIn('tipe', [
            'aset',
            'liabilitas',
            'ekuitas',
        ])->get();

        $result = [];
        foreach ($akuns as $akun) {
            $awal = (float) ($saldoAwal[$akun->id] ?? 0);
            $jurnal = $jurnalDebit[$akun->id] ?? null;

            if ($akun->tipe === 'aset') {
                $jurnalSaldo = $jurnal
                    ? (float) $jurnal->total_debit -
                        (float) $jurnal->total_kredit
                    : 0;
            } else {
                $jurnalSaldo = $jurnal
                    ? (float) $jurnal->total_kredit -
                        (float) $jurnal->total_debit
                    : 0;
            }

            $result[$akun->id] = $awal + $jurnalSaldo;
        }

        return $result;
    }
}
