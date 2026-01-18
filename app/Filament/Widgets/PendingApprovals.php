<?php

namespace App\Filament\Widgets;

use App\Models\BuktiTransfer;
use App\Models\IzinKeluar;
use App\Models\IzinPulang;
use App\Models\Konseling;
use App\Models\Pelanggaran;
use App\Models\Tahfidz;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PendingApprovals extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Perlu Persetujuan';

    protected function getStats(): array
    {
        $izinKeluar = IzinKeluar::where('status', 'pending')->count();
        $izinPulang = IzinPulang::where('status', 'pending')->count();
        $buktiTransfer = BuktiTransfer::where('status', 'pending')->count();
        $tahfidz = Tahfidz::where('status', 'pending')->count();
        $pelanggaran = Pelanggaran::where('status', 'pending')->count();
        $konseling = Konseling::where('perlu_tindak_lanjut', true)->count();

        return [
            Stat::make('Izin Keluar', $izinKeluar)
                ->description('Menunggu persetujuan')
                ->descriptionIcon('heroicon-m-clock')
                ->color($izinKeluar > 0 ? 'warning' : 'success')
                ->url(route('filament.auth.resources.izin-keluars.index')),

            Stat::make('Izin Pulang', $izinPulang)
                ->description('Menunggu persetujuan')
                ->descriptionIcon('heroicon-m-clock')
                ->color($izinPulang > 0 ? 'warning' : 'success')
                ->url(route('filament.auth.resources.izin-pulangs.index')),

            Stat::make('Bukti Transfer', $buktiTransfer)
                ->description('Perlu verifikasi')
                ->descriptionIcon('heroicon-m-document-check')
                ->color($buktiTransfer > 0 ? 'danger' : 'success')
                ->url(route('filament.auth.resources.bukti-transfers.index')),

            Stat::make('Tahfidz', $tahfidz)
                ->description('Menunggu persetujuan')
                ->descriptionIcon('heroicon-m-book-open')
                ->color($tahfidz > 0 ? 'warning' : 'success')
                ->url(route('filament.auth.resources.tahfidzs.index')),

            Stat::make('Pelanggaran', $pelanggaran)
                ->description('Belum ditangani')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($pelanggaran > 0 ? 'danger' : 'success')
                ->url(route('filament.auth.resources.pelanggarans.index')),

            Stat::make('Konseling', $konseling)
                ->description('Perlu tindak lanjut')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color($konseling > 0 ? 'warning' : 'success')
                ->url(route('filament.auth.resources.konselings.index')),
        ];
    }
}
