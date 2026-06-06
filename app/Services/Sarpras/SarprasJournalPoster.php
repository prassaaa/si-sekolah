<?php

namespace App\Services\Sarpras;

use App\Models\JurnalUmum;
use App\Models\SarprasBarang;
use App\Models\SarprasPengadaan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Posts balanced double-entry journal rows to jurnal_umums for sarpras events:
 * procurement intake and monthly depreciation.
 *
 * Accounts are resolved by convention via AkunResolver. If any required
 * account cannot be resolved, posting is SKIPPED with a Log::warning — never
 * guessed, never throws (lesson from audit C6). Stock intake / report math is
 * therefore never broken by a missing chart-of-accounts.
 *
 * All posting is idempotent: rows are tagged with jenis_referensi +
 * referensi_id (and, for depreciation, a periode marker in referensi) so they
 * are never double-posted and can be reversed.
 */
class SarprasJournalPoster
{
    public const JENIS_PENGADAAN = 'sarpras_pengadaan';

    public const JENIS_PENYUSUTAN = 'sarpras_penyusutan';

    public function __construct(
        private AkunResolver $akun,
        private PenyusutanService $penyusutan,
    ) {}

    /**
     * Post procurement intake: debit Aset Tetap total, credit Kas/Bank total.
     * Idempotent per pengadaan. Returns true when a journal was posted.
     */
    public function postPengadaan(SarprasPengadaan $pengadaan): bool
    {
        $total = (string) $pengadaan->total_biaya;

        if (bccomp($total, '0', 2) <= 0) {
            return false;
        }

        $debitAkunId = $this->akun->asetTetapAkunId();
        $kreditAkunId = $this->akun->kasAkunId();

        if ($debitAkunId === null || $kreditAkunId === null) {
            Log::warning('Sarpras pengadaan journal skipped: required akun not resolved.', [
                'jenis_referensi' => self::JENIS_PENGADAAN,
                'referensi_id' => $pengadaan->getKey(),
                'aset_tetap' => $debitAkunId,
                'kas' => $kreditAkunId,
            ]);

            return false;
        }

        if ($this->alreadyPostedPengadaan($pengadaan)) {
            return false;
        }

        $nomor = $pengadaan->nomor;
        $keterangan = 'Pengadaan sarpras '.$nomor;

        DB::transaction(function () use ($pengadaan, $debitAkunId, $kreditAkunId, $total, $nomor, $keterangan): void {
            $token = $this->freshToken();

            JurnalUmum::create([
                'nomor_bukti' => $nomor.'-AST-D-'.$token,
                'tanggal' => $pengadaan->tanggal,
                'keterangan' => $keterangan,
                'akun_id' => $debitAkunId,
                'debit' => $total,
                'kredit' => '0',
                'referensi' => $nomor,
                'jenis_referensi' => self::JENIS_PENGADAAN,
                'referensi_id' => $pengadaan->getKey(),
                'created_by' => $pengadaan->dibuat_oleh,
            ]);

            JurnalUmum::create([
                'nomor_bukti' => $nomor.'-AST-K-'.$token,
                'tanggal' => $pengadaan->tanggal,
                'keterangan' => $keterangan,
                'akun_id' => $kreditAkunId,
                'debit' => '0',
                'kredit' => $total,
                'referensi' => $nomor,
                'jenis_referensi' => self::JENIS_PENGADAAN,
                'referensi_id' => $pengadaan->getKey(),
                'created_by' => $pengadaan->dibuat_oleh,
            ]);
        });

        return true;
    }

    /**
     * Reverse (soft-delete) any procurement journal rows for a pengadaan.
     */
    public function reversePengadaan(SarprasPengadaan $pengadaan): void
    {
        JurnalUmum::query()
            ->where('jenis_referensi', self::JENIS_PENGADAAN)
            ->where('referensi_id', $pengadaan->getKey())
            ->delete();
    }

    /**
     * Post monthly depreciation for one asset for the given period: debit Beban
     * Penyusutan, credit Akumulasi Penyusutan. Idempotent per (barang, periode)
     * via the periode marker in `referensi`. Returns true when posted.
     */
    public function postPenyusutan(SarprasBarang $barang, Carbon $periode): bool
    {
        if (! $barang->isDepreciable()) {
            return false;
        }

        $akhirPeriode = $periode->copy()->endOfMonth();
        $nominal = $this->penyusutan->penyusutanPerBulan($barang);

        if (bccomp($nominal, '0', 2) <= 0) {
            return false;
        }

        // Do not depreciate beyond the depreciable base.
        $base = bcsub(
            (string) $barang->harga_perolehan,
            (string) $barang->nilai_residu,
            2,
        );
        $sudahDisusut = $this->penyusutan->akumulasiSampai($barang, $periode->copy()->subMonthNoOverflow()->endOfMonth());

        if (bccomp($sudahDisusut, $base, 2) >= 0) {
            return false;
        }

        $sisa = bcsub($base, $sudahDisusut, 2);
        if (bccomp($nominal, $sisa, 2) > 0) {
            $nominal = $sisa;
        }

        $debitAkunId = $this->akun->bebanPenyusutanAkunId();
        $kreditAkunId = $this->akun->akumulasiPenyusutanAkunId();

        $periodeMarker = $periode->format('Y-m');

        if ($debitAkunId === null || $kreditAkunId === null) {
            Log::warning('Sarpras penyusutan journal skipped: required akun not resolved.', [
                'jenis_referensi' => self::JENIS_PENYUSUTAN,
                'referensi_id' => $barang->getKey(),
                'periode' => $periodeMarker,
                'beban' => $debitAkunId,
                'akumulasi' => $kreditAkunId,
            ]);

            return false;
        }

        if ($this->alreadyPostedPenyusutan($barang, $periodeMarker)) {
            return false;
        }

        $referensi = 'SUSUT-'.$periodeMarker;
        $keterangan = 'Penyusutan '.$barang->nama.' periode '.$periodeMarker;

        DB::transaction(function () use ($barang, $debitAkunId, $kreditAkunId, $nominal, $referensi, $keterangan, $akhirPeriode): void {
            $token = $this->freshToken();

            JurnalUmum::create([
                'nomor_bukti' => $referensi.'-'.$barang->getKey().'-D-'.$token,
                'tanggal' => $akhirPeriode,
                'keterangan' => $keterangan,
                'akun_id' => $debitAkunId,
                'debit' => $nominal,
                'kredit' => '0',
                'referensi' => $referensi,
                'jenis_referensi' => self::JENIS_PENYUSUTAN,
                'referensi_id' => $barang->getKey(),
                'created_by' => null,
            ]);

            JurnalUmum::create([
                'nomor_bukti' => $referensi.'-'.$barang->getKey().'-K-'.$token,
                'tanggal' => $akhirPeriode,
                'keterangan' => $keterangan,
                'akun_id' => $kreditAkunId,
                'debit' => '0',
                'kredit' => $nominal,
                'referensi' => $referensi,
                'jenis_referensi' => self::JENIS_PENYUSUTAN,
                'referensi_id' => $barang->getKey(),
                'created_by' => null,
            ]);
        });

        return true;
    }

    private function alreadyPostedPengadaan(SarprasPengadaan $pengadaan): bool
    {
        return JurnalUmum::query()
            ->where('jenis_referensi', self::JENIS_PENGADAAN)
            ->where('referensi_id', $pengadaan->getKey())
            ->exists();
    }

    private function alreadyPostedPenyusutan(SarprasBarang $barang, string $periodeMarker): bool
    {
        return JurnalUmum::query()
            ->where('jenis_referensi', self::JENIS_PENYUSUTAN)
            ->where('referensi_id', $barang->getKey())
            ->where('referensi', 'SUSUT-'.$periodeMarker)
            ->exists();
    }

    /**
     * jurnal_umums.nomor_bukti is globally unique and ignores soft-deletes, so
     * reposts after a reversal must use a fresh token.
     */
    private function freshToken(): int
    {
        return ((int) JurnalUmum::query()->withTrashed()->max('id')) + 1;
    }
}
