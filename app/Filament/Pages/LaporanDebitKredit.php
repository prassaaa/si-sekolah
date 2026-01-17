<?php

namespace App\Filament\Pages;

use App\Models\KasKeluar;
use App\Models\KasMasuk;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class LaporanDebitKredit extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 8;

    protected static ?string $title = 'Laporan Debit Kredit';

    protected static ?string $slug = 'laporan/debit-kredit';

    protected string $view = 'filament.pages.laporan-debit-kredit';

    public ?string $jenis = 'all';

    public ?string $tanggal_mulai = null;

    public ?string $tanggal_selesai = null;

    public Collection $kasMasukData;

    public Collection $kasKeluarData;

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Debit & Kredit (Kas)';
    }

    public function mount(): void
    {
        $this->tanggal_mulai = now()->startOfMonth()->format('Y-m-d');
        $this->tanggal_selesai = now()->format('Y-m-d');
        $this->kasMasukData = collect();
        $this->kasKeluarData = collect();
        $this->filter();
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('jenis')
                    ->label('Jenis')
                    ->options([
                        'all' => 'Semua',
                        'masuk' => 'Kas Masuk (Debit)',
                        'keluar' => 'Kas Keluar (Kredit)',
                    ])
                    ->default('all')
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
                DatePicker::make('tanggal_mulai')
                    ->label('Dari Tanggal')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
                DatePicker::make('tanggal_selesai')
                    ->label('Sampai Tanggal')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
            ])
            ->columns(3);
    }

    public function filter(): void
    {
        if (! $this->tanggal_mulai || ! $this->tanggal_selesai) {
            $this->kasMasukData = collect();
            $this->kasKeluarData = collect();
            $this->summary = [];

            return;
        }

        // Kas Masuk
        if ($this->jenis === 'all' || $this->jenis === 'masuk') {
            $this->kasMasukData = KasMasuk::query()
                ->with('akun')
                ->whereBetween('tanggal', [$this->tanggal_mulai, $this->tanggal_selesai])
                ->orderBy('tanggal')
                ->get()
                ->map(fn ($k) => [
                    'tanggal' => $k->tanggal->format('d/m/Y'),
                    'nomor_bukti' => $k->nomor_bukti,
                    'akun' => $k->akun?->nama ?? '-',
                    'sumber' => $k->sumber,
                    'keterangan' => $k->keterangan,
                    'nominal' => $k->nominal,
                ]);
        } else {
            $this->kasMasukData = collect();
        }

        // Kas Keluar
        if ($this->jenis === 'all' || $this->jenis === 'keluar') {
            $this->kasKeluarData = KasKeluar::query()
                ->with('akun')
                ->whereBetween('tanggal', [$this->tanggal_mulai, $this->tanggal_selesai])
                ->orderBy('tanggal')
                ->get()
                ->map(fn ($k) => [
                    'tanggal' => $k->tanggal->format('d/m/Y'),
                    'nomor_bukti' => $k->nomor_bukti,
                    'akun' => $k->akun?->nama ?? '-',
                    'penerima' => $k->penerima,
                    'keterangan' => $k->keterangan,
                    'nominal' => $k->nominal,
                ]);
        } else {
            $this->kasKeluarData = collect();
        }

        $totalMasuk = $this->kasMasukData->sum('nominal');
        $totalKeluar = $this->kasKeluarData->sum('nominal');

        $this->summary = [
            'total_masuk' => $totalMasuk,
            'total_keluar' => $totalKeluar,
            'selisih' => $totalMasuk - $totalKeluar,
            'jml_masuk' => $this->kasMasukData->count(),
            'jml_keluar' => $this->kasKeluarData->count(),
        ];
    }
}
