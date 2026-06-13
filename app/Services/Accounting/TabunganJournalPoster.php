<?php

namespace App\Services\Accounting;

use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\TabunganSiswa;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Memosting pasangan jurnal double-entry seimbang ke jurnal_umums untuk
 * transaksi tabungan siswa.
 *
 * Tabungan diperlakukan sebagai TITIPAN/kewajiban (keputusan bisnis):
 *   - Setor : D Kas (1-1001)        / K Titipan Tabungan Siswa (2-1004)
 *   - Tarik : D Titipan (2-1004)    / K Kas (1-1001)
 *
 * Akun di-resolve dari kode pada config('akuntansi.akun.*'); bila salah satu
 * tidak ditemukan, posting dilewati dengan Log::warning (tidak pernah menebak).
 *
 * Cut-off: hanya transaksi bertanggal >= config('akuntansi.cutoff_posting')
 * yang diposting. Transaksi sebelum cut-off dianggap era pra-pembukuan otomatis
 * dan posisinya sudah diwakili oleh saldo awal.
 *
 * Invariant: saldo akun 2-1004 (SUM kredit - SUM debit) selalu sama dengan
 * SUM saldo terakhir seluruh siswa.
 *
 * Idempotensi: entri ditandai jenis_referensi + referensi_id sehingga tidak
 * pernah diposting ganda. Check-then-insert dilindungi lockForUpdate dalam
 * DB::transaction.
 */
class TabunganJournalPoster
{
    public const JENIS = 'tabungan_siswa';

    /**
     * Post pasangan seimbang untuk satu baris tabungan sesuai jenisnya.
     * Hanya diposting bila tanggal >= cut-off dan kedua akun ter-resolve.
     */
    public function post(TabunganSiswa $tabungan): void
    {
        if (! $this->isAfterCutoff($tabungan)) {
            return;
        }

        $kasAkunId = $this->resolveAkunId(config('akuntansi.akun.kas_default'));
        $titipanAkunId = $this->resolveAkunId(config('akuntansi.akun.titipan_tabungan'));

        if ($kasAkunId === null || $titipanAkunId === null) {
            Log::warning('Posting jurnal tabungan dilewati: akun kas/titipan tidak dapat di-resolve.', [
                'jenis_referensi' => self::JENIS,
                'referensi_id' => $tabungan->getKey(),
                'kas_akun_id' => $kasAkunId,
                'titipan_akun_id' => $titipanAkunId,
            ]);

            return;
        }

        [$debitAkunId, $kreditAkunId] = $tabungan->jenis === 'setor'
            ? [$kasAkunId, $titipanAkunId]
            : [$titipanAkunId, $kasAkunId];

        DB::transaction(function () use ($tabungan, $debitAkunId, $kreditAkunId): void {
            // Lock baris tabungan agar tidak ada proses lain yang memposting
            // secara bersamaan untuk record yang sama.
            $tabungan->getConnection()
                ->table($tabungan->getTable())
                ->where($tabungan->getKeyName(), $tabungan->getKey())
                ->lockForUpdate()
                ->first();

            if ($this->alreadyPosted($tabungan)) {
                return;
            }

            $nominal = (string) $tabungan->nominal;
            $tanggal = $tabungan->tanggal;
            $referensi = 'TAB-'.$tabungan->getKey();
            $keterangan = $tabungan->keterangan ?: $referensi;
            $referensiId = $tabungan->getKey();
            $createdBy = $tabungan->user_id;

            // Token unik untuk menghindari duplikasi nomor_bukti jurnal saat repost.
            $token = ((int) JurnalUmum::query()->withTrashed()->max('id')) + 1;

            JurnalUmum::create([
                'nomor_bukti' => $referensi.'-D-'.$token,
                'tanggal' => $tanggal,
                'keterangan' => $keterangan,
                'akun_id' => $debitAkunId,
                'debit' => $nominal,
                'kredit' => '0',
                'referensi' => $referensi,
                'jenis_referensi' => self::JENIS,
                'referensi_id' => $referensiId,
                'created_by' => $createdBy,
            ]);

            JurnalUmum::create([
                'nomor_bukti' => $referensi.'-K-'.$token,
                'tanggal' => $tanggal,
                'keterangan' => $keterangan,
                'akun_id' => $kreditAkunId,
                'debit' => '0',
                'kredit' => $nominal,
                'referensi' => $referensi,
                'jenis_referensi' => self::JENIS,
                'referensi_id' => $referensiId,
                'created_by' => $createdBy,
            ]);
        });
    }

    /**
     * Reverse (soft-delete) semua entri jurnal yang sebelumnya diposting untuk
     * suatu baris tabungan. Dipanggil sebelum repost atau saat dihapus.
     */
    public function reverse(string $jenisReferensi, TabunganSiswa $tabungan): void
    {
        JurnalUmum::query()
            ->where('jenis_referensi', $jenisReferensi)
            ->where('referensi_id', $tabungan->getKey())
            ->delete();
    }

    private function isAfterCutoff(TabunganSiswa $tabungan): bool
    {
        $cutoff = config('akuntansi.cutoff_posting');

        return Carbon::parse($tabungan->tanggal)->gte(Carbon::parse($cutoff));
    }

    private function resolveAkunId(?string $kode): ?int
    {
        if ($kode === null) {
            return null;
        }

        return Akun::query()->where('kode', $kode)->value('id');
    }

    private function alreadyPosted(TabunganSiswa $tabungan): bool
    {
        return JurnalUmum::query()
            ->where('jenis_referensi', self::JENIS)
            ->where('referensi_id', $tabungan->getKey())
            ->exists();
    }
}
