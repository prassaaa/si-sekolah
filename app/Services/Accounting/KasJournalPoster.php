<?php

namespace App\Services\Accounting;

use App\Models\Akun;
use App\Models\JurnalUmum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Memosting pasangan jurnal double-entry seimbang ke jurnal_umums untuk
 * transaksi kas (kas masuk / kas keluar).
 *
 * Setiap record kas membawa:
 *   - kas_akun_id : akun kas/bank yang bergerak (sisi kas)
 *   - akun_id     : akun lawan (pendapatan / beban)
 *
 * Jika kas_akun_id null, poster mencari akun kas secara konvensi:
 *   1. Akun dengan kode '1-1001' (Kas tunai)
 *   2. Akun tipe=aset, kategori=lancar, nama mengandung 'Kas' atau 'Bank'
 *
 * Idempotensi: entri ditandai jenis_referensi + referensi_id, sehingga
 * tidak pernah diposting ganda. Check-then-insert dilindungi lockForUpdate
 * dalam DB::transaction.
 */
class KasJournalPoster
{
    public const JENIS_KAS_MASUK = 'kas_masuk';

    public const JENIS_KAS_KELUAR = 'kas_keluar';

    /**
     * Post pasangan seimbang untuk KasMasuk: debit Kas, kredit akun lawan.
     *
     * @throws ValidationException
     */
    public function postKasMasuk(Model $kasMasuk): void
    {
        $kasAkunId = $kasMasuk->kas_akun_id ?? $this->resolveCashAkunId();

        $this->post(
            jenisReferensi: self::JENIS_KAS_MASUK,
            kas: $kasMasuk,
            debitAkunId: $kasAkunId,
            kreditAkunId: $kasMasuk->akun_id,
        );
    }

    /**
     * Post pasangan seimbang untuk KasKeluar: debit akun lawan, kredit Kas.
     *
     * @throws ValidationException
     */
    public function postKasKeluar(Model $kasKeluar): void
    {
        $kasAkunId = $kasKeluar->kas_akun_id ?? $this->resolveCashAkunId();

        $this->post(
            jenisReferensi: self::JENIS_KAS_KELUAR,
            kas: $kasKeluar,
            debitAkunId: $kasKeluar->akun_id,
            kreditAkunId: $kasAkunId,
        );
    }

    /**
     * Reverse (soft-delete) semua entri jurnal yang sebelumnya diposting
     * untuk suatu record kas. Dipanggil sebelum repost atau saat dihapus.
     */
    public function reverse(string $jenisReferensi, Model $kas): void
    {
        JurnalUmum::query()
            ->where('jenis_referensi', $jenisReferensi)
            ->where('referensi_id', $kas->getKey())
            ->delete();
    }

    /**
     * Resolve akun kas/bank secara konvensi jika kas_akun_id tidak diset.
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

    /**
     * Post satu pasangan debit/kredit di dalam transaction tunggal.
     * lockForUpdate pada record kas memastikan idempoten aman dari race.
     *
     * @throws ValidationException bila akun kas tidak ditemukan atau akun lawan sama
     */
    private function post(string $jenisReferensi, Model $kas, ?int $debitAkunId, ?int $kreditAkunId): void
    {
        // Jika akun kas tidak ditemukan, lewati posting tanpa error (data lama
        // mungkin belum punya kas_akun_id; form sudah mensyaratkan field ini).
        if ($debitAkunId === null || $kreditAkunId === null) {
            Log::warning('Posting jurnal kas dilewati: akun kas/bank tidak dapat di-resolve.', [
                'jenis_referensi' => $jenisReferensi,
                'referensi_id' => $kas->getKey(),
            ]);

            return;
        }

        DB::transaction(function () use ($jenisReferensi, $kas, $debitAkunId, $kreditAkunId): void {
            // Lock record sumber agar tidak ada proses lain yang memposting
            // secara bersamaan untuk kas yang sama.
            $kas->getConnection()
                ->table($kas->getTable())
                ->where($kas->getKeyName(), $kas->getKey())
                ->lockForUpdate()
                ->first();

            if ($debitAkunId === $kreditAkunId) {
                throw ValidationException::withMessages([
                    'akun_id' => 'Akun lawan tidak boleh sama dengan Akun Kas/Bank yang dipilih.',
                ]);
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

            // Token unik untuk menghindari duplikasi nomor_bukti jurnal saat repost.
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
        });
    }

    private function alreadyPosted(string $jenisReferensi, Model $kas): bool
    {
        return JurnalUmum::query()
            ->where('jenis_referensi', $jenisReferensi)
            ->where('referensi_id', $kas->getKey())
            ->exists();
    }
}
