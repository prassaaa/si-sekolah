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

class PerubahanModal extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;

    protected static \UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Perubahan Modal';

    protected static ?string $navigationLabel = 'Perubahan Modal';

    protected string $view = 'filament.pages.perubahan-modal';

    public ?string $tanggal_mulai = null;

    public ?string $tanggal_akhir = null;

    public float $modalAwal = 0;

    public float $labaRugi = 0;

    public float $prive = 0;

    public float $modalAkhir = 0;

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
        // Modal awal (saldo modal sebelum periode)
        $akunModal = Akun::where('tipe', 'modal')->pluck('id');
        $this->modalAwal = JurnalUmum::query()
            ->whereIn('akun_id', $akunModal)
            ->where('tanggal', '<', $this->tanggal_mulai)
            ->selectRaw('SUM(kredit) - SUM(debit) as saldo')
            ->value('saldo') ?? 0;

        // Laba Rugi periode
        $akunPendapatan = Akun::where('tipe', 'pendapatan')->pluck('id');
        $akunBeban = Akun::where('tipe', 'beban')->pluck('id');

        $pendapatan = JurnalUmum::query()
            ->whereIn('akun_id', $akunPendapatan)
            ->whereBetween('tanggal', [$this->tanggal_mulai, $this->tanggal_akhir])
            ->selectRaw('SUM(kredit) - SUM(debit) as total')
            ->value('total') ?? 0;

        $beban = JurnalUmum::query()
            ->whereIn('akun_id', $akunBeban)
            ->whereBetween('tanggal', [$this->tanggal_mulai, $this->tanggal_akhir])
            ->selectRaw('SUM(debit) - SUM(kredit) as total')
            ->value('total') ?? 0;

        $this->labaRugi = $pendapatan - $beban;

        // Prive (pengambilan pemilik)
        $this->prive = 0;

        $this->modalAkhir = $this->modalAwal + $this->labaRugi - $this->prive;
    }
}
