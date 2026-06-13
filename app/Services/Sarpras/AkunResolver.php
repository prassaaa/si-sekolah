<?php

namespace App\Services\Sarpras;

use App\Models\Akun;

/**
 * Resolves the chart-of-accounts entries needed to post sarpras journals.
 *
 * Resolution is by documented convention (kode first, then nama/kategori
 * heuristics). Every method returns null when no account matches so callers
 * can SKIP posting and log a warning rather than guessing — never throws,
 * never invents an account (lesson from audit C6).
 */
class AkunResolver
{
    /**
     * Aset Tetap account (debited on procurement). Convention: kode '1-4001',
     * else first aset/tetap account with a debit normal balance.
     */
    public function asetTetapAkunId(): ?int
    {
        $akun = Akun::query()->where('kode', '1-4001')->first();

        if (! $akun) {
            $akun = Akun::query()
                ->where('tipe', 'aset')
                ->where('kategori', 'tetap')
                ->where('posisi_normal', 'debit')
                ->orderBy('kode')
                ->first();
        }

        return $akun?->id;
    }

    /**
     * Cash/bank counterpart (credited on procurement). Convention: kode
     * '1-1001', else first aset/lancar account named like Kas/Bank.
     */
    public function kasAkunId(): ?int
    {
        $akun = Akun::query()->where('kode', '1-1001')->first();

        if (! $akun) {
            $akun = Akun::query()
                ->where('tipe', 'aset')
                ->where('kategori', 'lancar')
                ->where(function ($q): void {
                    $q->where('nama', 'like', '%Kas%')
                        ->orWhere('nama', 'like', '%Bank%');
                })
                ->orderBy('kode')
                ->first();
        }

        return $akun?->id;
    }

    /**
     * Beban Penyusutan account (debited monthly). Convention: kode '5-4001',
     * else first beban account named like "Penyusutan".
     */
    public function bebanPenyusutanAkunId(): ?int
    {
        $akun = Akun::query()->where('kode', '5-4001')->first();

        if (! $akun) {
            $akun = Akun::query()
                ->where('tipe', 'beban')
                ->where('nama', 'like', '%Penyusutan%')
                ->orderBy('kode')
                ->first();
        }

        return $akun?->id;
    }

    /**
     * Perlengkapan account (debited for bahan/habis-pakai procurement).
     * Convention: kode '1-3001', else first aset/lancar named like Perlengkapan,
     * else first beban account as fallback. Returns null when not resolvable.
     */
    public function perlengkapanAkunId(): ?int
    {
        $akun = Akun::query()->where('kode', '1-3001')->first();

        if (! $akun) {
            $akun = Akun::query()
                ->where('tipe', 'aset')
                ->where('kategori', 'lancar')
                ->where('nama', 'like', '%Perlengkapan%')
                ->orderBy('kode')
                ->first();
        }

        if (! $akun) {
            $akun = Akun::query()
                ->where('tipe', 'beban')
                ->orderBy('kode')
                ->first();
        }

        return $akun?->id;
    }

    /**
     * Akumulasi Penyusutan contra-asset (credited monthly). Convention: kode
     * '1-4002', else first aset account named like "Akumulasi Penyusutan"
     * with a credit normal balance.
     */
    public function akumulasiPenyusutanAkunId(): ?int
    {
        $akun = Akun::query()->where('kode', '1-4002')->first();

        if (! $akun) {
            $akun = Akun::query()
                ->where('tipe', 'aset')
                ->where('posisi_normal', 'kredit')
                ->where('nama', 'like', '%Akumulasi Penyusutan%')
                ->orderBy('kode')
                ->first();
        }

        return $akun?->id;
    }
}
