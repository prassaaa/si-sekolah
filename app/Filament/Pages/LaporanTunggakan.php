<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanTunggakanStats;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\TagihanSiswa;
use App\Services\Accounting\LaporanPdfService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanTunggakan extends Page implements HasSchemas, HasTable
{
    use ExposesTableToWidgets, InteractsWithSchemas, InteractsWithTable;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 50;

    protected static ?string $navigationLabel = 'Laporan Tunggakan';

    protected static ?string $title = 'Laporan Tunggakan';

    protected static ?string $slug = 'laporan/tunggakan';

    public ?string $activeTab = null;

    public ?Model $parentRecord = null;

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Tunggakan SPP';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    /**
     * Hitung bucket umur tunggakan berdasarkan selisih hari dari jatuh tempo.
     *
     * Bucket: '1-30 hari', '31-60 hari', '61-90 hari', '>90 hari'.
     * Tagihan belum melewati jatuh tempo dikembalikan sebagai 'Belum Jatuh Tempo'.
     */
    public static function bucketUmur(Carbon|string|null $tanggalJatuhTempo): string
    {
        if (! $tanggalJatuhTempo) {
            return 'Belum Jatuh Tempo';
        }

        $jatuhTempo = $tanggalJatuhTempo instanceof Carbon
            ? $tanggalJatuhTempo
            : Carbon::parse($tanggalJatuhTempo);

        $selisihHari = (int) $jatuhTempo->diffInDays(Carbon::now(), absolute: false);

        if ($selisihHari <= 0) {
            return 'Belum Jatuh Tempo';
        }

        if ($selisihHari <= 30) {
            return '1-30 hari';
        }

        if ($selisihHari <= 60) {
            return '31-60 hari';
        }

        if ($selisihHari <= 90) {
            return '61-90 hari';
        }

        return '>90 hari';
    }

    public function table(Table $table): Table
    {
        $activeSemesterId = Semester::query()->where('is_active', true)->value('id');

        return $table
            ->query(
                TagihanSiswa::query()
                    ->with(['siswa.kelas', 'jenisPembayaran'])
                    ->whereIn('status', ['belum_bayar', 'sebagian'])
                    ->where('sisa_tagihan', '>', 0)
                    ->where('tanggal_jatuh_tempo', '<', Carbon::now())
                    ->orderByDesc('sisa_tagihan')
            )
            ->columns([
                TextColumn::make('siswa.nama_lengkap')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('siswa.kelas.nama')
                    ->label('Kelas')
                    ->sortable(),
                TextColumn::make('jenisPembayaran.nama')
                    ->label('Jenis Tagihan')
                    ->sortable(),
                TextColumn::make('tanggal_jatuh_tempo')
                    ->label('Jatuh Tempo')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('umur_tunggakan')
                    ->label('Umur Tunggakan')
                    ->state(fn (TagihanSiswa $record): string => self::bucketUmur($record->tanggal_jatuh_tempo))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '1-30 hari' => 'warning',
                        '31-60 hari' => 'orange',
                        '61-90 hari' => 'danger',
                        '>90 hari' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('sisa_tagihan')
                    ->label('Sisa Tunggakan')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('danger')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sebagian' => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'belum_bayar' => 'Belum Bayar',
                        'sebagian' => 'Sebagian',
                        default => ucfirst($state),
                    })
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('semester_id')
                    ->label('Semester')
                    ->options(Semester::query()->orderByDesc('tahun_ajaran_id')->pluck('nama', 'id'))
                    ->default($activeSemesterId),
                SelectFilter::make('kelas')
                    ->label('Kelas')
                    ->options(Kelas::query()->where('is_active', true)->orderBy('tingkat')->orderBy('nama')->pluck('nama', 'id'))
                    ->query(fn ($query, array $data) => $data['value'] ? $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $data['value'])) : $query),
                SelectFilter::make('umur_bucket')
                    ->label('Bucket Umur')
                    ->options([
                        '1-30' => '1-30 hari',
                        '31-60' => '31-60 hari',
                        '61-90' => '61-90 hari',
                        '>90' => '>90 hari',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        $now = Carbon::now();

                        return match ($data['value']) {
                            '1-30' => $query->whereBetween('tanggal_jatuh_tempo', [
                                $now->copy()->subDays(30)->startOfDay(),
                                $now->copy()->subDay()->endOfDay(),
                            ]),
                            '31-60' => $query->whereBetween('tanggal_jatuh_tempo', [
                                $now->copy()->subDays(60)->startOfDay(),
                                $now->copy()->subDays(31)->endOfDay(),
                            ]),
                            '61-90' => $query->whereBetween('tanggal_jatuh_tempo', [
                                $now->copy()->subDays(90)->startOfDay(),
                                $now->copy()->subDays(61)->endOfDay(),
                            ]),
                            '>90' => $query->where('tanggal_jatuh_tempo', '<', $now->copy()->subDays(90)->startOfDay()),
                            default => $query,
                        };
                    }),
            ])
            ->deferFilters(false)
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada tunggakan')
            ->emptyStateDescription('Semua tagihan sudah dibayar atau belum melewati jatuh tempo.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanTunggakanStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cetakPdf')
                ->label('Cetak PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(function (): StreamedResponse {
                    $records = TagihanSiswa::query()
                        ->with(['siswa.kelas', 'jenisPembayaran'])
                        ->whereIn('status', ['belum_bayar', 'sebagian'])
                        ->where('sisa_tagihan', '>', 0)
                        ->where('tanggal_jatuh_tempo', '<', Carbon::now())
                        ->orderByDesc('sisa_tagihan')
                        ->get();

                    $no = 1;
                    $baris = $records->map(function (TagihanSiswa $tagihan) use (&$no): array {
                        return [
                            (string) $no++,
                            $tagihan->siswa?->nama_lengkap ?? '-',
                            $tagihan->siswa?->kelas?->nama ?? '-',
                            $tagihan->tanggal_jatuh_tempo?->format('d/m/Y') ?? '-',
                            self::bucketUmur($tagihan->tanggal_jatuh_tempo),
                            'Rp '.number_format((float) $tagihan->sisa_tagihan, 0, ',', '.'),
                        ];
                    })->toArray();

                    $totalSisa = $records->sum('sisa_tagihan');

                    $pdf = LaporanPdfService::make()
                        ->judul('LAPORAN TUNGGAKAN SPP')
                        ->periode('Per '.Carbon::now()->translatedFormat('d F Y'))
                        ->kolom([
                            'No',
                            'Nama Siswa',
                            'Kelas',
                            'Jatuh Tempo',
                            'Umur Tunggakan',
                            ['Sisa Tunggakan', 'right'],
                        ])
                        ->baris($baris)
                        ->ringkasan([
                            [
                                '',
                                'TOTAL TUNGGAKAN',
                                '',
                                '',
                                '',
                                'Rp '.number_format($totalSisa, 0, ',', '.'),
                            ],
                        ])
                        ->landscape()
                        ->render();

                    $namaFile = LaporanPdfService::make()
                        ->namaFile('laporan-tunggakan-spp-'.Carbon::now()->format('Ymd'));

                    return response()->streamDownload(
                        fn () => print ($pdf->output()),
                        $namaFile,
                    );
                }),
        ];
    }
}
