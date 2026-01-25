<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanTagihanSiswaStats;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\TagihanSiswa;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class LaporanTagihanSiswa extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 9;

    protected static ?string $title = 'Laporan Tagihan Siswa';

    protected static ?string $slug = 'laporan/tagihan-siswa';

    protected string $view = 'filament.pages.laporan-tagihan-siswa';

    public ?int $semester_id = null;

    public ?int $kelas_id = null;

    public ?string $status = null;

    public Collection $data;

    public array $summary = [];

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Tagihan Siswa';
    }

    public function mount(): void
    {
        $this->semester_id = Semester::query()
            ->where('is_active', true)
            ->value('id');

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
                Select::make('kelas_id')
                    ->label('Kelas')
                    ->options(Kelas::query()->where('is_active', true)->orderBy('tingkat')->orderBy('nama')->pluck('nama', 'id'))
                    ->placeholder('Semua Kelas')
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'belum_bayar' => 'Belum Bayar',
                        'sebagian' => 'Sebagian',
                        'lunas' => 'Lunas',
                    ])
                    ->placeholder('Semua Status')
                    ->live()
                    ->afterStateUpdated(fn () => $this->filter()),
            ])
            ->columns(3);
    }

    public function filter(): void
    {
        if (! $this->semester_id) {
            $this->data = collect();
            $this->summary = [];

            return;
        }

        $query = TagihanSiswa::query()
            ->with(['siswa.kelas', 'jenisPembayaran'])
            ->where('semester_id', $this->semester_id);

        if ($this->kelas_id) {
            $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $this->kelas_id));
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        $tagihans = $query->orderBy('tanggal_jatuh_tempo')->get();

        $this->data = $tagihans->map(fn ($t) => [
            'nomor_tagihan' => $t->nomor_tagihan,
            'nis' => $t->siswa?->nis ?? '-',
            'nama' => $t->siswa?->nama_lengkap ?? '-',
            'kelas' => $t->siswa?->kelas?->nama ?? '-',
            'jenis' => $t->jenisPembayaran?->nama ?? '-',
            'total_tagihan' => $t->total_tagihan,
            'terbayar' => $t->total_terbayar,
            'sisa' => $t->sisa_tagihan,
            'jatuh_tempo' => $t->tanggal_jatuh_tempo?->format('d/m/Y') ?? '-',
            'status' => $t->status,
        ]);

        $this->summary = [
            'total_tagihan' => $tagihans->sum('total_tagihan'),
            'total_terbayar' => $tagihans->sum('total_terbayar'),
            'total_sisa' => $tagihans->sum('sisa_tagihan'),
            'jumlah_tagihan' => $tagihans->count(),
            'belum_bayar' => $tagihans->where('status', 'belum_bayar')->count(),
            'sebagian' => $tagihans->where('status', 'sebagian')->count(),
            'lunas' => $tagihans->where('status', 'lunas')->count(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanTagihanSiswaStats::make([
                'summary' => $this->summary,
            ]),
        ];
    }
}
