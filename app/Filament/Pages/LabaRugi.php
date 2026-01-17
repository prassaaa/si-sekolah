<?php

namespace App\Filament\Pages;

use App\Models\Akun;
use App\Models\JurnalUmum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class LabaRugi extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static \UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Laporan Laba Rugi';

    protected static ?string $navigationLabel = 'Laba Rugi';

    protected string $view = 'filament.pages.laba-rugi';

    public ?string $tanggal_mulai = null;

    public ?string $tanggal_akhir = null;

    /** @var array<int, array<string, mixed>> */
    public array $pendapatan = [];

    /** @var array<int, array<string, mixed>> */
    public array $beban = [];

    public float $totalPendapatan = 0;

    public float $totalBeban = 0;

    public float $labaRugi = 0;

    public function mount(): void
    {
        $this->tanggal_mulai = now()->startOfMonth()->format('Y-m-d');
        $this->tanggal_akhir = now()->format('Y-m-d');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('tanggal_mulai')
                    ->label('Tanggal Mulai')
                    ->required(),
                DatePicker::make('tanggal_akhir')
                    ->label('Tanggal Akhir')
                    ->required(),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function filter(): void
    {
        // Pendapatan (tipe = pendapatan)
        $akunPendapatan = Akun::where('tipe', 'pendapatan')->pluck('id');
        $this->pendapatan = JurnalUmum::query()
            ->whereIn('akun_id', $akunPendapatan)
            ->whereBetween('tanggal', [$this->tanggal_mulai, $this->tanggal_akhir])
            ->with('akun')
            ->get()
            ->groupBy('akun_id')
            ->map(fn ($items) => [
                'akun' => $items->first()->akun?->nama,
                'nominal' => $items->sum('kredit') - $items->sum('debit'),
            ])->values()->toArray();

        // Beban (tipe = beban)
        $akunBeban = Akun::where('tipe', 'beban')->pluck('id');
        $this->beban = JurnalUmum::query()
            ->whereIn('akun_id', $akunBeban)
            ->whereBetween('tanggal', [$this->tanggal_mulai, $this->tanggal_akhir])
            ->with('akun')
            ->get()
            ->groupBy('akun_id')
            ->map(fn ($items) => [
                'akun' => $items->first()->akun?->nama,
                'nominal' => $items->sum('debit') - $items->sum('kredit'),
            ])->values()->toArray();

        $this->totalPendapatan = collect($this->pendapatan)->sum('nominal');
        $this->totalBeban = collect($this->beban)->sum('nominal');
        $this->labaRugi = $this->totalPendapatan - $this->totalBeban;
    }
}
