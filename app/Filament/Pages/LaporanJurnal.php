<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanJurnalStats;
use App\Models\JurnalUmum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class LaporanJurnal extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Laporan Jurnal';

    protected static ?string $slug = 'laporan/jurnal';

    protected string $view = 'filament.pages.laporan-jurnal';

    public ?string $tanggal_mulai = null;

    public ?string $tanggal_selesai = null;

    public Collection $data;

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Jurnal Umum';
    }

    public function mount(): void
    {
        $this->tanggal_mulai = now()->startOfMonth()->format('Y-m-d');
        $this->tanggal_selesai = now()->format('Y-m-d');

        $this->data = collect();
        $this->filter();
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
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
            ->columns(2);
    }

    public function filter(): void
    {
        if (! $this->tanggal_mulai || ! $this->tanggal_selesai) {
            $this->data = collect();
            $this->summary = [];

            return;
        }

        $this->data = JurnalUmum::query()
            ->with('akun')
            ->whereBetween('tanggal', [$this->tanggal_mulai, $this->tanggal_selesai])
            ->orderBy('tanggal')
            ->orderBy('nomor_bukti')
            ->get()
            ->map(fn ($j) => [
                'tanggal' => $j->tanggal->format('d/m/Y'),
                'nomor_bukti' => $j->nomor_bukti,
                'akun' => $j->akun?->nama ?? '-',
                'keterangan' => $j->keterangan,
                'debit' => $j->debit,
                'kredit' => $j->kredit,
            ]);

        $this->summary = [
            'total_debit' => $this->data->sum('debit'),
            'total_kredit' => $this->data->sum('kredit'),
            'total_transaksi' => $this->data->count(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanJurnalStats::make([
                'summary' => $this->summary,
            ]),
        ];
    }
}
