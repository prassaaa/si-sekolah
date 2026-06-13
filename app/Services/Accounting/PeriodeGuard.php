<?php

namespace App\Services\Accounting;

use App\Models\PeriodeAkuntansi;
use App\Providers\AppServiceProvider;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Penjaga periode akuntansi: memastikan tidak ada transaksi yang dibuat,
 * diubah, atau dihapus pada periode (bulan/tahun) yang telah ditutup.
 *
 * Didaftarkan terpusat di {@see AppServiceProvider::boot()}
 * sebagai listener event `saving` dan `deleting` pada setiap model transaksi
 * keuangan, sehingga model transaksinya sendiri tidak perlu disentuh.
 */
class PeriodeGuard
{
    /**
     * Lempar ValidationException bila tanggal yang diberikan jatuh pada
     * periode akuntansi yang sudah ditutup. Tanggal null dilewati (tidak
     * ada periode yang bisa ditentukan).
     *
     * @throws ValidationException
     */
    public function assertOpen(mixed $tanggal): void
    {
        $date = $this->parseTanggal($tanggal);

        if ($date === null) {
            return;
        }

        $tahun = (int) $date->format('Y');
        $bulan = (int) $date->format('n');

        if (PeriodeAkuntansi::isClosed($tahun, $bulan)) {
            throw ValidationException::withMessages([
                'tanggal' => sprintf(
                    'Periode %d/%d sudah ditutup. Transaksi tidak dapat diubah.',
                    $bulan,
                    $tahun,
                ),
            ]);
        }
    }

    /**
     * Normalisasi berbagai bentuk input tanggal menjadi instance Carbon.
     * Mengembalikan null bila input kosong atau tidak dapat diuraikan.
     */
    private function parseTanggal(mixed $tanggal): ?CarbonInterface
    {
        if ($tanggal === null || $tanggal === '') {
            return null;
        }

        if ($tanggal instanceof CarbonInterface) {
            return $tanggal;
        }

        if ($tanggal instanceof DateTimeInterface) {
            return Carbon::instance($tanggal);
        }

        try {
            return Carbon::parse($tanggal);
        } catch (Throwable) {
            return null;
        }
    }
}
