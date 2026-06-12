<?php

namespace App\Filament\Pages;

use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\SaldoAwal;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class BukuBesar extends Page implements HasSchemas, HasTable
{
    use HasPageShield;
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static \UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Buku Besar';

    protected static ?string $navigationLabel = 'Buku Besar';

    public ?string $activeTab = null;

    public ?Model $parentRecord = null;

    public function getTitle(): string|Htmlable
    {
        return 'Buku Besar';
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
                return $this->buildLedger($filters);
            })
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y'),
                TextColumn::make('nomor_bukti')
                    ->label('No. Bukti'),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(50),
                TextColumn::make('debit')
                    ->label('Debit')
                    ->money('IDR')
                    ->alignEnd(),
                TextColumn::make('kredit')
                    ->label('Kredit')
                    ->money('IDR')
                    ->alignEnd(),
                TextColumn::make('saldo')
                    ->label('Saldo')
                    ->money('IDR')
                    ->alignEnd()
                    ->weight('bold'),
            ])
            ->filters([
                SelectFilter::make('akun_id')
                    ->label('Akun')
                    ->options(Akun::query()->orderBy('kode')->pluck('nama', 'id'))
                    ->searchable(),
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
                            $indicators[] = 'Dari: '.Carbon::parse($data['tanggal_mulai'])->translatedFormat('d M Y');
                        }

                        if ($data['tanggal_akhir'] ?? null) {
                            $indicators[] = 'Sampai: '.Carbon::parse($data['tanggal_akhir'])->translatedFormat('d M Y');
                        }

                        return $indicators;
                    }),
            ])
            ->deferFilters(false)
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada data')
            ->emptyStateDescription('Silakan pilih akun dan rentang tanggal untuk melihat buku besar.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    /**
     * Build a real ledger for a single account: opening balance seeded from
     * saldo_awals plus prior journal movement, a running saldo for each entry
     * in the period (ordered by tanggal then id, honoring posisi_normal), and
     * explicit opening/closing rows.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function buildLedger(array $filters): Collection
    {
        $akunId = $filters['akun_id']['value'] ?? null;
        $tanggalMulai = $filters['tanggal']['tanggal_mulai'] ?? null;
        $tanggalAkhir = $filters['tanggal']['tanggal_akhir'] ?? null;

        if (! $akunId) {
            return collect();
        }

        $akun = Akun::query()->find($akunId);

        if (! $akun) {
            return collect();
        }

        $isDebitNormal = $akun->posisi_normal === 'debit';

        $saldoAwalRecorded = SaldoAwal::query()
            ->where('akun_id', $akunId)
            ->when($tanggalMulai, fn ($q) => $q->where('tanggal', '<', $tanggalMulai))
            ->sum('saldo');

        $priorMovement = JurnalUmum::query()
            ->where('akun_id', $akunId)
            ->when($tanggalMulai, fn ($q) => $q->where('tanggal', '<', $tanggalMulai))
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(kredit), 0) as total_kredit')
            ->first();

        $priorDebit = (string) ($priorMovement->total_debit ?? '0');
        $priorKredit = (string) ($priorMovement->total_kredit ?? '0');
        $priorNet = $isDebitNormal
            ? bcsub($priorDebit, $priorKredit, 2)
            : bcsub($priorKredit, $priorDebit, 2);

        $saldo = bcadd((string) $saldoAwalRecorded, $priorNet, 2);

        $rows = collect();
        $rows->push([
            'tanggal' => $tanggalMulai ? Carbon::parse($tanggalMulai) : null,
            'nomor_bukti' => '-',
            'keterangan' => 'Saldo Awal',
            'debit' => '0',
            'kredit' => '0',
            'saldo' => $saldo,
        ]);

        $entries = JurnalUmum::query()
            ->where('akun_id', $akunId)
            ->when($tanggalMulai, fn ($q) => $q->where('tanggal', '>=', $tanggalMulai))
            ->when($tanggalAkhir, fn ($q) => $q->where('tanggal', '<=', $tanggalAkhir))
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        foreach ($entries as $entry) {
            $debit = (string) $entry->debit;
            $kredit = (string) $entry->kredit;
            $net = $isDebitNormal
                ? bcsub($debit, $kredit, 2)
                : bcsub($kredit, $debit, 2);
            $saldo = bcadd($saldo, $net, 2);

            $rows->push([
                'tanggal' => $entry->tanggal,
                'nomor_bukti' => $entry->nomor_bukti,
                'keterangan' => $entry->keterangan,
                'debit' => $debit,
                'kredit' => $kredit,
                'saldo' => $saldo,
            ]);
        }

        $rows->push([
            'tanggal' => $tanggalAkhir ? Carbon::parse($tanggalAkhir) : null,
            'nomor_bukti' => '-',
            'keterangan' => 'Saldo Akhir',
            'debit' => '0',
            'kredit' => '0',
            'saldo' => $saldo,
        ]);

        return $rows->values();
    }
}
