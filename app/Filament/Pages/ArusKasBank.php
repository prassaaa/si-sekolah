<?php

namespace App\Filament\Pages;

use App\Models\KasKeluar;
use App\Models\KasMasuk;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ArusKasBank extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static \UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Arus Kas/Bank';

    protected static ?string $navigationLabel = 'Arus Kas/Bank';

    protected string $view = 'filament.pages.arus-kas-bank';

    public ?string $tanggal_mulai = null;

    public ?string $tanggal_akhir = null;

    public float $totalMasuk = 0;

    public float $totalKeluar = 0;

    public float $selisih = 0;

    /** @var array<int, array<string, mixed>> */
    public array $kasMasuk = [];

    /** @var array<int, array<string, mixed>> */
    public array $kasKeluar = [];

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
        $this->kasMasuk = KasMasuk::query()
            ->whereBetween('tanggal', [$this->tanggal_mulai, $this->tanggal_akhir])
            ->with('akun')
            ->get()
            ->map(fn ($item) => [
                'tanggal' => $item->tanggal->format('d/m/Y'),
                'nomor_bukti' => $item->nomor_bukti,
                'akun' => $item->akun?->nama,
                'sumber' => $item->sumber,
                'nominal' => $item->nominal,
            ])->toArray();

        $this->kasKeluar = KasKeluar::query()
            ->whereBetween('tanggal', [$this->tanggal_mulai, $this->tanggal_akhir])
            ->with('akun')
            ->get()
            ->map(fn ($item) => [
                'tanggal' => $item->tanggal->format('d/m/Y'),
                'nomor_bukti' => $item->nomor_bukti,
                'akun' => $item->akun?->nama,
                'penerima' => $item->penerima,
                'nominal' => $item->nominal,
            ])->toArray();

        $this->totalMasuk = collect($this->kasMasuk)->sum('nominal');
        $this->totalKeluar = collect($this->kasKeluar)->sum('nominal');
        $this->selisih = $this->totalMasuk - $this->totalKeluar;
    }
}
