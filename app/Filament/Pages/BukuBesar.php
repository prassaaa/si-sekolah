<?php

namespace App\Filament\Pages;

use App\Models\Akun;
use App\Models\JurnalUmum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class BukuBesar extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static \UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Buku Besar';

    protected static ?string $navigationLabel = 'Buku Besar';

    protected string $view = 'filament.pages.buku-besar';

    public ?int $akun_id = null;

    public ?string $tanggal_mulai = null;

    public ?string $tanggal_akhir = null;

    /** @var array<int, array<string, mixed>> */
    public array $entries = [];

    public float $saldoAwal = 0;

    public float $saldoAkhir = 0;

    public function mount(): void
    {
        $this->tanggal_mulai = now()->startOfMonth()->format('Y-m-d');
        $this->tanggal_akhir = now()->format('Y-m-d');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('akun_id')
                    ->label('Akun')
                    ->options(Akun::query()->pluck('nama', 'id'))
                    ->searchable()
                    ->required(),
                DatePicker::make('tanggal_mulai')
                    ->label('Tanggal Mulai')
                    ->required(),
                DatePicker::make('tanggal_akhir')
                    ->label('Tanggal Akhir')
                    ->required(),
            ])
            ->columns(3)
            ->statePath('data');
    }

    public function filter(): void
    {
        if (! $this->akun_id) {
            return;
        }

        $query = JurnalUmum::query()
            ->where('akun_id', $this->akun_id)
            ->whereBetween('tanggal', [$this->tanggal_mulai, $this->tanggal_akhir])
            ->orderBy('tanggal')
            ->orderBy('id');

        $this->entries = $query->get()->map(function ($jurnal) {
            return [
                'tanggal' => $jurnal->tanggal->format('d/m/Y'),
                'keterangan' => $jurnal->keterangan,
                'debit' => $jurnal->debit,
                'kredit' => $jurnal->kredit,
            ];
        })->toArray();

        $saldoSebelum = JurnalUmum::query()
            ->where('akun_id', $this->akun_id)
            ->where('tanggal', '<', $this->tanggal_mulai)
            ->selectRaw('SUM(debit) - SUM(kredit) as saldo')
            ->value('saldo') ?? 0;

        $this->saldoAwal = $saldoSebelum;

        $totalDebit = collect($this->entries)->sum('debit');
        $totalKredit = collect($this->entries)->sum('kredit');

        $this->saldoAkhir = $this->saldoAwal + $totalDebit - $totalKredit;
    }
}
