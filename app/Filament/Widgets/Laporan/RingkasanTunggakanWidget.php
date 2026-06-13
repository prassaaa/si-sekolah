<?php

namespace App\Filament\Widgets\Laporan;

use App\Models\Kelas;
use App\Models\TagihanSiswa;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class RingkasanTunggakanWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Ringkasan Tunggakan';

    /**
     * Widget hanya ditampilkan kepada pengguna yang memiliki permission
     * View:DashboardKeuangan.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('View:DashboardKeuangan') ?? false;
    }

    protected function getStats(): array
    {
        /** Tunggakan: status belum_bayar atau sebagian, kecualikan batal. */
        $baseQuery = TagihanSiswa::belumLunas();

        $totalTunggakan = (clone $baseQuery)->sum('sisa_tagihan');
        $jumlahSiswa = (clone $baseQuery)
            ->distinct('siswa_id')
            ->count('siswa_id');

        /** Jumlah kelas dengan siswa menunggak. */
        $jumlahKelas = (clone $baseQuery)
            ->whereHas('siswa', fn ($q) => $q->whereNotNull('kelas_id'))
            ->with('siswa:id,kelas_id')
            ->get()
            ->pluck('siswa.kelas_id')
            ->filter()
            ->unique()
            ->count();

        /** Top 5 kelas dengan tunggakan terbesar. */
        $tunggakanPerKelas = DB::table('tagihan_siswas')
            ->join('siswas', 'siswas.id', '=', 'tagihan_siswas.siswa_id')
            ->join('kelas', 'kelas.id', '=', 'siswas.kelas_id')
            ->whereIn('tagihan_siswas.status', ['belum_bayar', 'sebagian'])
            ->whereNull('tagihan_siswas.deleted_at')
            ->whereNull('siswas.deleted_at')
            ->select('kelas.nama', DB::raw('SUM(tagihan_siswas.sisa_tagihan) as total_tunggakan'))
            ->groupBy('kelas.id', 'kelas.nama')
            ->orderByDesc('total_tunggakan')
            ->limit(3)
            ->pluck('total_tunggakan', 'nama');

        $deskripsiKelas = $tunggakanPerKelas->isEmpty()
            ? 'Tidak ada tunggakan'
            : $tunggakanPerKelas
                ->map(fn ($nominal, $kelas) => "{$kelas}: Rp ".number_format((float) $nominal, 0, ',', '.'))
                ->implode(' | ');

        return [
            Stat::make(
                'Total Tunggakan',
                'Rp '.number_format((float) $totalTunggakan, 0, ',', '.'),
            )
                ->description('Sisa tagihan belum lunas')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color($totalTunggakan > 0 ? 'danger' : 'success'),

            Stat::make(
                'Siswa Menunggak',
                number_format($jumlahSiswa),
            )
                ->description("{$jumlahKelas} kelas memiliki tunggakan")
                ->descriptionIcon('heroicon-m-user-group')
                ->color($jumlahSiswa > 0 ? 'warning' : 'success'),

            Stat::make(
                'Top 3 Kelas Tunggakan',
                '',
            )
                ->description($deskripsiKelas)
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),
        ];
    }
}
