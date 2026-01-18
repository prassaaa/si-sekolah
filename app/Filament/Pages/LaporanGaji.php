<?php

namespace App\Filament\Pages;

use App\Models\SlipGaji;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class LaporanGaji extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Laporan Gaji';

    protected static ?string $slug = 'laporan/gaji';

    protected string $view = 'filament.pages.laporan-gaji';

    public ?string $bulan = null;

    public ?string $status = null;

    public Collection $data;

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Slip Gaji';
    }

    public function mount(): void
    {
        $this->bulan = now()->format('Y-m');
        $this->data = collect();
        $this->filter();
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('bulan')
                    ->label('Bulan')
                    ->displayFormat('F Y')
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'disetujui' => 'Disetujui',
                        'dibayar' => 'Dibayar',
                    ])
                    ->placeholder('Semua Status')
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
            ])
            ->columns(2);
    }

    public function filter(): void
    {
        if (! $this->bulan) {
            $this->data = collect();
            $this->summary = [];

            return;
        }

        $carbonDate = \Carbon\Carbon::parse($this->bulan);
        $tahun = $carbonDate->year;
        $bulan = $carbonDate->month;

        $query = SlipGaji::query()
            ->with('pegawai')
            ->where('tahun', $tahun)
            ->where('bulan', $bulan);

        if ($this->status) {
            $query->where('status', $this->status);
        }

        $slips = $query->get();

        $this->data = $slips->map(fn ($s) => [
            'nip' => $s->pegawai?->nip ?? '-',
            'nama' => $s->pegawai?->nama_lengkap ?? '-',
            'gaji_pokok' => $s->gaji_pokok,
            'total_tunjangan' => $s->total_tunjangan,
            'total_potongan' => $s->total_potongan,
            'gaji_bersih' => $s->gaji_bersih,
            'status' => $s->status,
        ]);

        $this->summary = [
            'total_pegawai' => $slips->count(),
            'total_gaji_pokok' => $slips->sum('gaji_pokok'),
            'total_tunjangan' => $slips->sum('total_tunjangan'),
            'total_potongan' => $slips->sum('total_potongan'),
            'total_gaji_bersih' => $slips->sum('gaji_bersih'),
        ];
    }
}
