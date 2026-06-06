<?php

namespace App\Services\Sarpras;

use App\Models\SarprasBarang;
use Illuminate\Support\Carbon;

/**
 * Straight-line / declining-balance depreciation calculations for sarpras
 * assets. All money math uses bc-math at scale 2; this service is read-only
 * and never touches the ledger.
 */
class PenyusutanService
{
    private const SCALE = 2;

    /**
     * Depreciation per month for an asset, as a bc-math string at scale 2.
     *
     * Straight-line: (harga_perolehan - nilai_residu) / umur_ekonomis_bulan.
     * Returns '0.00' when the asset is not depreciable (metode 'tanpa'/null or
     * no economic life).
     */
    public function penyusutanPerBulan(SarprasBarang $barang): string
    {
        $umur = $barang->resolveUmurEkonomisBulan();

        if (! $barang->isDepreciable() || $umur === null || $umur <= 0) {
            return '0.00';
        }

        $harga = $this->money($barang->harga_perolehan);
        $residu = $this->money($barang->nilai_residu);

        $base = bcsub($harga, $residu, self::SCALE);

        if (bccomp($base, '0', self::SCALE) <= 0) {
            return '0.00';
        }

        return bcdiv($base, (string) $umur, self::SCALE);
    }

    /**
     * Accumulated depreciation from tanggal_perolehan up to (and including) the
     * given date, capped at the depreciable base (harga - residu).
     */
    public function akumulasiSampai(SarprasBarang $barang, Carbon $sampai): string
    {
        if (! $barang->isDepreciable() || $barang->tanggal_perolehan === null) {
            return '0.00';
        }

        $perBulan = $this->penyusutanPerBulan($barang);

        if (bccomp($perBulan, '0', self::SCALE) <= 0) {
            return '0.00';
        }

        $mulai = Carbon::parse($barang->tanggal_perolehan)->startOfDay();
        $akhir = $sampai->copy()->startOfDay();

        if ($akhir->lessThan($mulai)) {
            return '0.00';
        }

        $bulanBerjalan = $mulai->diffInMonths($akhir);

        $akumulasi = bcmul($perBulan, (string) $bulanBerjalan, self::SCALE);

        $base = bcsub(
            $this->money($barang->harga_perolehan),
            $this->money($barang->nilai_residu),
            self::SCALE,
        );

        if (bccomp($akumulasi, $base, self::SCALE) > 0) {
            return $base;
        }

        return $akumulasi;
    }

    /**
     * Book value at the given date: harga_perolehan - akumulasi, floored at the
     * residual value.
     */
    public function nilaiBuku(SarprasBarang $barang, Carbon $sampai): string
    {
        $harga = $this->money($barang->harga_perolehan);
        $residu = $this->money($barang->nilai_residu);

        $nilaiBuku = bcsub($harga, $this->akumulasiSampai($barang, $sampai), self::SCALE);

        if (bccomp($nilaiBuku, $residu, self::SCALE) < 0) {
            return $residu;
        }

        return $nilaiBuku;
    }

    private function money(mixed $value): string
    {
        return bcadd((string) ($value ?? '0'), '0', self::SCALE);
    }
}
