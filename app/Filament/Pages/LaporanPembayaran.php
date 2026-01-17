<?php

namespace App\Filament\Pages;

use App\Models\JenisPembayaran;
use App\Models\Semester;
use App\Models\TagihanSiswa;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use UnitEnum;

class LaporanPembayaran extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Laporan Pembayaran';

    protected static ?string $slug = 'laporan/pembayaran';

    protected string $view = 'filament.pages.laporan-pembayaran';

    public ?int $semester_id = null;

    public ?int $jenis_pembayaran_id = null;

    public ?string $tanggal_mulai = null;

    public ?string $tanggal_selesai = null;

    public Collection $data;

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Rekap Pembayaran';
    }

    public function mount(): void
    {
        $this->semester_id = Semester::query()
            ->where('is_active', true)
            ->value('id');

        $this->tanggal_mulai = now()->startOfMonth()->format('Y-m-d');
        $this->tanggal_selesai = now()->format('Y-m-d');

        $this->data = collect();
        $this->filter();
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('semester_id')
                    ->label('Semester')
                    ->options(Semester::query()->orderByDesc('tahun_ajaran_id')->pluck('nama', 'id'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
                Select::make('jenis_pembayaran_id')
                    ->label('Jenis Pembayaran')
                    ->options(JenisPembayaran::query()->where('is_active', true)->pluck('nama', 'id'))
                    ->placeholder('Semua Jenis')
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
                DatePicker::make('tanggal_mulai')
                    ->label('Dari Tanggal')
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
                DatePicker::make('tanggal_selesai')
                    ->label('Sampai Tanggal')
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
            ])
            ->columns(4);
    }

    public function filter(): void
    {
        if (! $this->semester_id) {
            $this->data = collect();
            $this->summary = [];

            return;
        }

        $query = TagihanSiswa::query()
            ->with(['siswa.kelas', 'jenisPembayaran', 'pembayarans'])
            ->where('semester_id', $this->semester_id);

        if ($this->jenis_pembayaran_id) {
            $query->where('jenis_pembayaran_id', $this->jenis_pembayaran_id);
        }

        $tagihans = $query->get();

        // Group by jenis pembayaran
        $this->data = $tagihans->groupBy('jenis_pembayaran_id')->map(function ($items) {
            $jenis = $items->first()->jenisPembayaran;
            $pembayarans = $items->flatMap(fn ($t) => $t->pembayarans);

            // Filter by date if specified
            if ($this->tanggal_mulai) {
                $pembayarans = $pembayarans->filter(
                    fn ($p) => $p->tanggal_bayar >= $this->tanggal_mulai
                );
            }
            if ($this->tanggal_selesai) {
                $pembayarans = $pembayarans->filter(
                    fn ($p) => $p->tanggal_bayar <= $this->tanggal_selesai
                );
            }

            return [
                'jenis' => $jenis?->nama ?? '-',
                'total_tagihan' => $items->sum('total_tagihan'),
                'total_terbayar' => $pembayarans->where('status', 'berhasil')->sum('jumlah_bayar'),
                'total_sisa' => $items->sum('sisa_tagihan'),
                'jumlah_siswa' => $items->pluck('siswa_id')->unique()->count(),
                'lunas' => $items->where('status', 'lunas')->count(),
                'belum_lunas' => $items->whereIn('status', ['belum_bayar', 'sebagian'])->count(),
            ];
        })->values();

        $this->summary = [
            'total_tagihan' => $this->data->sum('total_tagihan'),
            'total_terbayar' => $this->data->sum('total_terbayar'),
            'total_sisa' => $this->data->sum('total_sisa'),
            'persentase' => $this->data->sum('total_tagihan') > 0
                ? round(($this->data->sum('total_terbayar') / $this->data->sum('total_tagihan')) * 100, 1)
                : 0,
        ];
    }
}
