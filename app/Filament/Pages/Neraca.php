<?php

namespace App\Filament\Pages;

use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\SaldoAwal;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

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
        // Aset
        $akunAset = Akun::where('tipe', 'aset')->get();
        $this->aset = $akunAset->map(function ($akun) {
            $saldoAwal = SaldoAwal::where('akun_id', $akun->id)
                ->where('tanggal', '<=', $this->tanggal)
                ->sum('saldo');

            $jurnalSaldo = JurnalUmum::where('akun_id', $akun->id)
                ->where('tanggal', '<=', $this->tanggal)
                ->selectRaw('SUM(debit) - SUM(kredit) as saldo')
                ->value('saldo') ?? 0;

            return [
                'akun' => $akun->nama,
                'saldo' => $saldoAwal + $jurnalSaldo,
            ];
        })->filter(fn ($item) => $item['saldo'] != 0)->values()->toArray();

        // Kewajiban
        $akunKewajiban = Akun::where('tipe', 'kewajiban')->get();
        $this->kewajiban = $akunKewajiban->map(function ($akun) {
            $saldoAwal = SaldoAwal::where('akun_id', $akun->id)
                ->where('tanggal', '<=', $this->tanggal)
                ->sum('saldo');

            $jurnalSaldo = JurnalUmum::where('akun_id', $akun->id)
                ->where('tanggal', '<=', $this->tanggal)
                ->selectRaw('SUM(kredit) - SUM(debit) as saldo')
                ->value('saldo') ?? 0;

            return [
                'akun' => $akun->nama,
                'saldo' => $saldoAwal + $jurnalSaldo,
            ];
        })->filter(fn ($item) => $item['saldo'] != 0)->values()->toArray();

        // Modal
        $akunModal = Akun::where('tipe', 'modal')->get();
        $this->modal = $akunModal->map(function ($akun) {
            $saldoAwal = SaldoAwal::where('akun_id', $akun->id)
                ->where('tanggal', '<=', $this->tanggal)
                ->sum('saldo');

            $jurnalSaldo = JurnalUmum::where('akun_id', $akun->id)
                ->where('tanggal', '<=', $this->tanggal)
                ->selectRaw('SUM(kredit) - SUM(debit) as saldo')
                ->value('saldo') ?? 0;

            return [
                'akun' => $akun->nama,
                'saldo' => $saldoAwal + $jurnalSaldo,
            ];
        })->filter(fn ($item) => $item['saldo'] != 0)->values()->toArray();

        $this->totalAset = collect($this->aset)->sum('saldo');
        $this->totalKewajiban = collect($this->kewajiban)->sum('saldo');
        $this->totalModal = collect($this->modal)->sum('saldo');
    }
}
