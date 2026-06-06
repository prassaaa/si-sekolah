<?php

namespace App\Services\Accounting;

use App\Models\Akun;
use App\Models\JurnalUmum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Posts balanced double-entry journal rows to jurnal_umums for kas (cash)
 * transactions.
 *
 * Each kas record carries ONE akun_id (the counterpart, e.g. a revenue or
 * expense account). Double-entry also needs the cash/bank account. The schema
 * does not designate a cash akun, so we resolve it by convention:
 *
 *   1. Akun with kode '1-1001' (the seeded "Kas" account), else
 *   2. Akun with tipe='aset', kategori='lancar' whose nama contains
 *      'Kas' or 'Bank'.
 *
 * If the cash akun cannot be resolved, we SKIP posting and log a warning
 * rather than guessing wrong — kas saving is never broken by this service.
 *
 * Posting is idempotent: entries are tagged with jenis_referensi +
 * referensi_id pointing back to the kas row, so they are never double-posted
 * and can be excluded from reports that count manual entries.
 */
class KasJournalPoster
{
    public const JENIS_KAS_MASUK = 'kas_masuk';

    public const JENIS_KAS_KELUAR = 'kas_keluar';

    /**
     * Post a balanced pair for a KasMasuk row: debit Cash, credit counterpart.
     */
    public function postKasMasuk(Model $kasMasuk): void
    {
        $this->post(
            jenisReferensi: self::JENIS_KAS_MASUK,
            kas: $kasMasuk,
            debitAkunId: $this->resolveCashAkunId(),
            kreditAkunId: $kasMasuk->akun_id,
        );
    }

    /**
     * Post a balanced pair for a KasKeluar row: debit counterpart, credit Cash.
     */
    public function postKasKeluar(Model $kasKeluar): void
    {
        $this->post(
            jenisReferensi: self::JENIS_KAS_KELUAR,
            kas: $kasKeluar,
            debitAkunId: $kasKeluar->akun_id,
            kreditAkunId: $this->resolveCashAkunId(),
        );
    }

    /**
     * Reverse (soft-delete) any journal rows previously posted for a kas row.
     */
    public function reverse(string $jenisReferensi, Model $kas): void
    {
        JurnalUmum::query()
            ->where('jenis_referensi', $jenisReferensi)
            ->where('referensi_id', $kas->getKey())
            ->delete();
    }

    /**
     * Resolve the cash/bank account id by documented convention, or null.
     */
    public function resolveCashAkunId(): ?int
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

    private function post(string $jenisReferensi, Model $kas, ?int $debitAkunId, int $kreditAkunId): void
    {
        if ($debitAkunId === null || $kreditAkunId === null) {
            Log::warning('Kas journal posting skipped: cash akun could not be resolved.', [
                'jenis_referensi' => $jenisReferensi,
                'referensi_id' => $kas->getKey(),
            ]);

            return;
        }

        if ($debitAkunId === $kreditAkunId) {
            Log::warning('Kas journal posting skipped: counterpart equals cash akun.', [
                'jenis_referensi' => $jenisReferensi,
                'referensi_id' => $kas->getKey(),
            ]);

            return;
        }

        if ($this->alreadyPosted($jenisReferensi, $kas)) {
            return;
        }

        $nominal = (string) $kas->nominal;
        $tanggal = $kas->tanggal;
        $nomorBukti = $kas->nomor_bukti;
        $keterangan = $kas->keterangan ?: $nomorBukti;
        $referensiId = $kas->getKey();
        $createdBy = $kas->user_id;

        // jurnal_umums.nomor_bukti is globally unique and the index ignores
        // soft-deletes, so reposts (after a reversal) must use a fresh token.
        $token = ((int) JurnalUmum::query()->withTrashed()->max('id')) + 1;

        JurnalUmum::create([
            'nomor_bukti' => $nomorBukti.'-D-'.$token,
            'tanggal' => $tanggal,
            'keterangan' => $keterangan,
            'akun_id' => $debitAkunId,
            'debit' => $nominal,
            'kredit' => '0',
            'referensi' => $nomorBukti,
            'jenis_referensi' => $jenisReferensi,
            'referensi_id' => $referensiId,
            'created_by' => $createdBy,
        ]);

        JurnalUmum::create([
            'nomor_bukti' => $nomorBukti.'-K-'.$token,
            'tanggal' => $tanggal,
            'keterangan' => $keterangan,
            'akun_id' => $kreditAkunId,
            'debit' => '0',
            'kredit' => $nominal,
            'referensi' => $nomorBukti,
            'jenis_referensi' => $jenisReferensi,
            'referensi_id' => $referensiId,
            'created_by' => $createdBy,
        ]);
    }

    private function alreadyPosted(string $jenisReferensi, Model $kas): bool
    {
        return JurnalUmum::query()
            ->where('jenis_referensi', $jenisReferensi)
            ->where('referensi_id', $kas->getKey())
            ->exists();
    }
}
