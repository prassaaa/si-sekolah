<?php

namespace App\Services\Sarpras;

use App\Models\SarprasBarang;
use Illuminate\Support\Carbon;

/**
 * Straight-line / declining-balance depreciation calculations for sarpras
 * assets. All money math uses bc-math at scale 2; this service is read-only
 * and never touches the ledger.
 *
 * Bulan berjalan selalu dibulatkan ke BAWAH menjadi bilangan bulat
 * ((int) floor(diffInMonths)) agar akumulasi laporan konsisten dengan jurnal
 * penyusutan yang diposting per bulan penuh (akun Akumulasi 1-4002). Pecahan
 * bulan tidak pernah dijurnal, sehingga tidak boleh ikut dihitung di laporan.
 */
class PenyusutanService
{
    private const SCALE = 2;

    /**
     * Depreciation for the first month of an asset, as a bc-math string at
     * scale 2. Dipakai sebagai kolom "Penyusutan/Bulan" laporan dan sebagai
     * nominal jurnal bulanan untuk metode garis lurus.
     *
     * - Garis lurus: (harga_perolehan - nilai_residu) / umur_ekonomis_bulan.
     * - Saldo menurun: tarif bulanan (2 / umur) atas nilai buku awal
     *   (harga_perolehan), karena tiap bulan menyusut atas nilai buku berjalan.
     *
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

        if ($this->isSaldoMenurun($barang)) {
            return $this->penyusutanSaldoMenurunBulan($harga, $umur);
        }

        return bcdiv($base, (string) $umur, self::SCALE);
    }

    /**
     * Accumulated depreciation from tanggal_perolehan up to (and including) the
     * given date, capped at the depreciable base (harga - residu).
     *
     * Bulan berjalan = (int) floor(diffInMonths) — hanya bulan penuh dihitung.
     */
    public function akumulasiSampai(SarprasBarang $barang, Carbon $sampai): string
    {
        if (! $barang->isDepreciable() || $barang->tanggal_perolehan === null) {
            return '0.00';
        }

        $umur = $barang->resolveUmurEkonomisBulan();

        if ($umur === null || $umur <= 0) {
            return '0.00';
        }

        $mulai = Carbon::parse($barang->tanggal_perolehan)->startOfDay();
        $akhir = $sampai->copy()->startOfDay();

        if ($akhir->lessThan($mulai)) {
            return '0.00';
        }

        $bulanBerjalan = (int) floor($mulai->diffInMonths($akhir));

        if ($bulanBerjalan <= 0) {
            return '0.00';
        }

        $base = bcsub(
            $this->money($barang->harga_perolehan),
            $this->money($barang->nilai_residu),
            self::SCALE,
        );

        if (bccomp($base, '0', self::SCALE) <= 0) {
            return '0.00';
        }

        $akumulasi = $this->isSaldoMenurun($barang)
            ? $this->akumulasiSaldoMenurun($barang, $bulanBerjalan, $umur, $base)
            : bcmul($this->penyusutanPerBulan($barang), (string) $bulanBerjalan, self::SCALE);

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

    private function isSaldoMenurun(SarprasBarang $barang): bool
    {
        return $barang->metode_susut === 'saldo_menurun';
    }

    /**
     * Tarif saldo menurun (double-declining) per bulan = 2 / umur, dibulatkan
     * di scale internal yang lebih tinggi agar pembulatan tidak menumpuk.
     */
    private function tarifSaldoMenurun(int $umur): string
    {
        return bcdiv('2', (string) $umur, 10);
    }

    /**
     * Penyusutan bulan pertama untuk metode saldo menurun: tarif x harga
     * perolehan (nilai buku awal).
     */
    private function penyusutanSaldoMenurunBulan(string $harga, int $umur): string
    {
        return bcmul($harga, $this->tarifSaldoMenurun($umur), self::SCALE);
    }

    /**
     * Akumulasi penyusutan saldo menurun setelah n bulan penuh.
     *
     * Tiap bulan: penyusutan = tarif x nilai buku berjalan; nilai buku tidak
     * boleh turun di bawah residu. Akumulasi maksimum = base (harga - residu).
     * Iterasi per bulan (n dibatasi umur ekonomis) mempertahankan ketepatan
     * declining-balance yang path-dependent.
     */
    private function akumulasiSaldoMenurun(SarprasBarang $barang, int $bulan, int $umur, string $base): string
    {
        $tarif = $this->tarifSaldoMenurun($umur);
        $residu = $this->money($barang->nilai_residu);

        $nilaiBuku = $this->money($barang->harga_perolehan);
        $akumulasi = '0.00';

        for ($i = 0; $i < $bulan; $i++) {
            if (bccomp($akumulasi, $base, self::SCALE) >= 0) {
                break;
            }

            $penyusutan = bcmul($nilaiBuku, $tarif, self::SCALE);

            $sisa = bcsub($base, $akumulasi, self::SCALE);
            if (bccomp($penyusutan, $sisa, self::SCALE) > 0) {
                $penyusutan = $sisa;
            }

            if (bccomp($penyusutan, '0', self::SCALE) <= 0) {
                break;
            }

            $akumulasi = bcadd($akumulasi, $penyusutan, self::SCALE);
            $nilaiBuku = bcsub($nilaiBuku, $penyusutan, self::SCALE);

            if (bccomp($nilaiBuku, $residu, self::SCALE) < 0) {
                $nilaiBuku = $residu;
            }
        }

        return $akumulasi;
    }

    private function money(mixed $value): string
    {
        return bcadd((string) ($value ?? '0'), '0', self::SCALE);
    }
}
