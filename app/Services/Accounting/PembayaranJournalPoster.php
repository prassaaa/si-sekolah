<?php

namespace App\Services\Accounting;

use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\Pembayaran;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Memosting pasangan jurnal double-entry seimbang ke jurnal_umums untuk
 * Pembayaran SPP/siswa, dengan basis KAS (pendapatan diakui saat dibayar).
 *
 * Satu Pembayaran berhasil menghasilkan satu pasangan:
 *   - DEBIT  : akun kas/bank (di-resolve dari UnitPos.akun_id; fallback ke
 *              akun kode config('akuntansi.akun.kas_default')).
 *   - KREDIT : akun pendapatan (di-resolve dari
 *              tagihanSiswa.jenisPembayaran.akun_pendapatan_id; fallback ke
 *              akun kode config('akuntansi.akun.pendapatan_spp_default')).
 *
 * Posting hanya dilakukan bila Pembayaran:
 *   - status === 'berhasil',
 *   - belum di-soft-delete, dan
 *   - tanggal_bayar >= config('akuntansi.cutoff_posting').
 * Bila salah satu syarat gagal, posting dilewati dan jurnal lama (bila ada)
 * di-reverse — sehingga perubahan status menyebabkan jurnal ikut hilang.
 *
 * Idempotensi: entri ditandai jenis_referensi + referensi_id, sehingga tidak
 * pernah diposting ganda. Check-then-insert dilindungi lockForUpdate dalam
 * DB::transaction. TIDAK pernah melempar exception (skip + Log::warning bila
 * akun tidak dapat di-resolve) agar penyimpanan Pembayaran tidak terblokir.
 *
 * Mengikuti pola KasJournalPoster (Wave 1).
 */
class PembayaranJournalPoster
{
    public const JENIS = 'pembayaran';

    /**
     * Post pasangan seimbang untuk Pembayaran berhasil: debit Kas, kredit Pendapatan.
     * Bila Pembayaran tidak memenuhi syarat posting, jurnal yang ada di-reverse.
     */
    public function post(Pembayaran $pembayaran): void
    {
        if (! $this->shouldPost($pembayaran)) {
            $this->reverse($pembayaran);

            return;
        }

        $debitAkunId = $this->resolveKasAkunId($pembayaran);
        $kreditAkunId = $this->resolvePendapatanAkunId($pembayaran);

        if ($debitAkunId === null || $kreditAkunId === null) {
            Log::warning('Posting jurnal pembayaran dilewati: akun kas atau akun pendapatan tidak dapat di-resolve.', [
                'jenis_referensi' => self::JENIS,
                'referensi_id' => $pembayaran->getKey(),
                'kas_akun_id' => $debitAkunId,
                'pendapatan_akun_id' => $kreditAkunId,
            ]);

            return;
        }

        if ($debitAkunId === $kreditAkunId) {
            Log::warning('Posting jurnal pembayaran dilewati: akun kas sama dengan akun pendapatan.', [
                'jenis_referensi' => self::JENIS,
                'referensi_id' => $pembayaran->getKey(),
                'akun_id' => $debitAkunId,
            ]);

            return;
        }

        DB::transaction(function () use ($pembayaran, $debitAkunId, $kreditAkunId): void {
            $pembayaran->getConnection()
                ->table($pembayaran->getTable())
                ->where($pembayaran->getKeyName(), $pembayaran->getKey())
                ->lockForUpdate()
                ->first();

            if ($this->alreadyPosted($pembayaran)) {
                return;
            }

            $nominal = (string) $pembayaran->jumlah_bayar;
            $tanggal = $pembayaran->tanggal_bayar;
            $nomor = $pembayaran->nomor_transaksi;
            $keterangan = $pembayaran->keterangan ?: ('Pembayaran '.$nomor);
            $referensiId = $pembayaran->getKey();
            $createdBy = auth()->id();

            $token = ((int) JurnalUmum::query()->withTrashed()->max('id')) + 1;

            JurnalUmum::create([
                'nomor_bukti' => $nomor.'-D-'.$token,
                'tanggal' => $tanggal,
                'keterangan' => $keterangan,
                'akun_id' => $debitAkunId,
                'debit' => $nominal,
                'kredit' => '0',
                'referensi' => $nomor,
                'jenis_referensi' => self::JENIS,
                'referensi_id' => $referensiId,
                'created_by' => $createdBy,
            ]);

            JurnalUmum::create([
                'nomor_bukti' => $nomor.'-K-'.$token,
                'tanggal' => $tanggal,
                'keterangan' => $keterangan,
                'akun_id' => $kreditAkunId,
                'debit' => '0',
                'kredit' => $nominal,
                'referensi' => $nomor,
                'jenis_referensi' => self::JENIS,
                'referensi_id' => $referensiId,
                'created_by' => $createdBy,
            ]);
        });
    }

    /**
     * Reverse (soft-delete) semua entri jurnal yang sebelumnya diposting untuk
     * Pembayaran ini. Dipanggil sebelum repost atau saat batal/hapus.
     */
    public function reverse(Pembayaran $pembayaran): void
    {
        JurnalUmum::query()
            ->where('jenis_referensi', self::JENIS)
            ->where('referensi_id', $pembayaran->getKey())
            ->delete();
    }

    /**
     * Pembayaran layak diposting hanya bila berhasil, belum dihapus, dan
     * tanggal bayarnya tidak sebelum cut-off posting otomatis.
     */
    private function shouldPost(Pembayaran $pembayaran): bool
    {
        if ($pembayaran->status !== 'berhasil') {
            return false;
        }

        if ($pembayaran->deleted_at !== null) {
            return false;
        }

        if ($pembayaran->tanggal_bayar === null) {
            return false;
        }

        $cutoff = Carbon::parse(config('akuntansi.cutoff_posting'));

        return Carbon::parse($pembayaran->tanggal_bayar)->gte($cutoff);
    }

    /**
     * Resolve akun kas (sisi debit) dari UnitPos pembayaran; fallback ke akun
     * kas default berdasarkan kode konfigurasi.
     */
    private function resolveKasAkunId(Pembayaran $pembayaran): ?int
    {
        $akunId = $pembayaran->unitPos?->akun_id;

        if ($akunId !== null) {
            return (int) $akunId;
        }

        return Akun::query()
            ->where('kode', config('akuntansi.akun.kas_default'))
            ->value('id');
    }

    /**
     * Resolve akun pendapatan (sisi kredit) dari JenisPembayaran tagihan;
     * fallback ke akun pendapatan SPP default berdasarkan kode konfigurasi.
     */
    private function resolvePendapatanAkunId(Pembayaran $pembayaran): ?int
    {
        $akunId = $pembayaran->tagihanSiswa?->jenisPembayaran?->akun_pendapatan_id;

        if ($akunId !== null) {
            return (int) $akunId;
        }

        return Akun::query()
            ->where('kode', config('akuntansi.akun.pendapatan_spp_default'))
            ->value('id');
    }

    private function alreadyPosted(Pembayaran $pembayaran): bool
    {
        return JurnalUmum::query()
            ->where('jenis_referensi', self::JENIS)
            ->where('referensi_id', $pembayaran->getKey())
            ->exists();
    }
}
