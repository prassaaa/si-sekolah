<?php

namespace App\Filament\Pages;

use App\Models\Akun;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class Neraca extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static \UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Neraca';

    protected static ?string $navigationLabel = 'Neraca';

    protected string $view = 'filament.pages.neraca';

    public ?string $tanggal = null;

    /** @var array<int, array<string, mixed>> */
    public array $aset = [];

    /** @var array<int, array<string, mixed>> */
    public array $kewajiban = [];

    /** @var array<int, array<string, mixed>> */
    public array $modal = [];

    public float $totalAset = 0;

    public float $totalKewajiban = 0;

    public float $totalModal = 0;

    public function mount(): void
    {
        $this->tanggal = now()->format('Y-m-d');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('tanggal')
                    ->label('Per Tanggal')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
            ])
            ->columns(1);
    }

    public function filter(): void
    {
        $saldoPerAkun = $this->calculateSaldoPerAkun($this->tanggal);

        $akuns = Akun::whereIn('tipe', [
            'aset',
            'liabilitas',
            'ekuitas',
        ])->get();

        $this->aset = $akuns
            ->where('tipe', 'aset')
            ->map(
                fn ($akun) => [
                    'akun' => $akun->nama,
                    'saldo' => $saldoPerAkun[$akun->id] ?? 0,
                ],
            )
            ->filter(fn ($item) => $item['saldo'] != 0)
            ->values()
            ->toArray();

        $this->kewajiban = $akuns
            ->where('tipe', 'liabilitas')
            ->map(
                fn ($akun) => [
                    'akun' => $akun->nama,
                    'saldo' => $saldoPerAkun[$akun->id] ?? 0,
                ],
            )
            ->filter(fn ($item) => $item['saldo'] != 0)
            ->values()
            ->toArray();

        $this->modal = $akuns
            ->where('tipe', 'ekuitas')
            ->map(
                fn ($akun) => [
                    'akun' => $akun->nama,
                    'saldo' => $saldoPerAkun[$akun->id] ?? 0,
                ],
            )
            ->filter(fn ($item) => $item['saldo'] != 0)
            ->values()
            ->toArray();

        $this->totalAset = collect($this->aset)->sum('saldo');
        $this->totalKewajiban = collect($this->kewajiban)->sum('saldo');
        $this->totalModal = collect($this->modal)->sum('saldo');
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
