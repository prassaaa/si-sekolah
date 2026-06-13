<?php

namespace App\Services\Accounting;

use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\SlipGaji;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Memosting jurnal akrual beban gaji untuk slip gaji yang sudah di-approve.
 *
 * Hanya menangani sisi AKRUAL (pengakuan beban + kewajiban):
 *   - Pegawai guru (jabatan.jenis = 'Fungsional') :
 *       D Beban Gaji Guru (5-1001)     / K Hutang Gaji (2-1002)
 *   - Pegawai non-guru (karyawan)      :
 *       D Beban Gaji Karyawan (5-1002) / K Hutang Gaji (2-1002)
 *
 * Sisi PEMBAYARAN (D Hutang Gaji / K Kas) TIDAK ditangani di sini: pembayaran
 * dilakukan dengan membuat record KasKeluar (lihat SlipGaji::bayar) yang
 * otomatis dijurnal oleh KasKeluarObserver.
 *
 * Akun di-resolve dari kode pada config('akuntansi.akun.*'); bila salah satu
 * tidak ditemukan, posting dilewati dengan Log::warning (tidak pernah menebak).
 *
 * Cut-off: hanya akrual bertanggal >= config('akuntansi.cutoff_posting') yang
 * diposting. Tanggal akrual = approved_at slip (fallback now()). Akrual sebelum
 * cut-off dianggap era pra-pembukuan otomatis (posisinya diwakili saldo awal).
 *
 * Idempotensi: entri ditandai jenis_referensi + referensi_id sehingga tidak
 * pernah diposting ganda. Check-then-insert dilindungi lockForUpdate dalam
 * DB::transaction.
 */
class SlipGajiJournalPoster
{
    public const JENIS = 'slip_gaji_akrual';

    /**
     * Post pasangan akrual seimbang (D Beban Gaji / K Hutang Gaji) untuk slip.
     *
     * Hanya diposting bila:
     *   - status slip sudah 'approved' atau 'paid', DAN
     *   - tanggal akrual (approved_at ?? now) >= cut-off, DAN
     *   - kedua akun (beban gaji + hutang gaji) ter-resolve.
     */
    public function postAkrual(SlipGaji $slip): void
    {
        if (! in_array($slip->status, ['approved', 'paid'], true)) {
            return;
        }

        if (! $this->isAfterCutoff($slip)) {
            return;
        }

        $bebanKode = $slip->pegawai?->isGuru()
            ? config('akuntansi.akun.beban_gaji_guru')
            : config('akuntansi.akun.beban_gaji_karyawan');

        $bebanAkunId = $this->resolveAkunId($bebanKode);
        $hutangAkunId = $this->resolveAkunId(config('akuntansi.akun.hutang_gaji'));

        if ($bebanAkunId === null || $hutangAkunId === null) {
            Log::warning('Posting jurnal akrual gaji dilewati: akun beban/hutang gaji tidak dapat di-resolve.', [
                'jenis_referensi' => self::JENIS,
                'referensi_id' => $slip->getKey(),
                'beban_akun_id' => $bebanAkunId,
                'hutang_akun_id' => $hutangAkunId,
            ]);

            return;
        }

        DB::transaction(function () use ($slip, $bebanAkunId, $hutangAkunId): void {
            // Lock baris slip agar tidak ada proses lain yang memposting
            // secara bersamaan untuk record yang sama.
            $slip->getConnection()
                ->table($slip->getTable())
                ->where($slip->getKeyName(), $slip->getKey())
                ->lockForUpdate()
                ->first();

            if ($this->alreadyPosted($slip)) {
                return;
            }

            $nominal = (string) $slip->gaji_bersih;
            $tanggal = $this->tanggalAkrual($slip)->toDateString();
            $referensi = $slip->nomor;
            $keterangan = 'Akrual beban gaji '.$slip->nomor;
            $referensiId = $slip->getKey();
            $createdBy = $slip->created_by;

            // Token unik untuk menghindari duplikasi nomor_bukti jurnal saat repost.
            $token = ((int) JurnalUmum::query()->withTrashed()->max('id')) + 1;

            JurnalUmum::create([
                'nomor_bukti' => $referensi.'-AKR-D-'.$token,
                'tanggal' => $tanggal,
                'keterangan' => $keterangan,
                'akun_id' => $bebanAkunId,
                'debit' => $nominal,
                'kredit' => '0',
                'referensi' => $referensi,
                'jenis_referensi' => self::JENIS,
                'referensi_id' => $referensiId,
                'created_by' => $createdBy,
            ]);

            JurnalUmum::create([
                'nomor_bukti' => $referensi.'-AKR-K-'.$token,
                'tanggal' => $tanggal,
                'keterangan' => $keterangan,
                'akun_id' => $hutangAkunId,
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
     * Reverse (soft-delete) semua entri jurnal akrual yang sebelumnya diposting
     * untuk suatu slip. Dipanggil saat slip dihapus.
     */
    public function reverseAkrual(SlipGaji $slip): void
    {
        JurnalUmum::query()
            ->where('jenis_referensi', self::JENIS)
            ->where('referensi_id', $slip->getKey())
            ->delete();
    }

    /**
     * Tanggal akrual = approved_at bila ada, jika tidak now().
     */
    private function tanggalAkrual(SlipGaji $slip): Carbon
    {
        return $slip->approved_at
            ? Carbon::parse($slip->approved_at)
            : Carbon::now();
    }

    private function isAfterCutoff(SlipGaji $slip): bool
    {
        $cutoff = config('akuntansi.cutoff_posting');

        return $this->tanggalAkrual($slip)->gte(Carbon::parse($cutoff));
    }

    private function resolveAkunId(?string $kode): ?int
    {
        if ($kode === null) {
            return null;
        }

        return Akun::query()->where('kode', $kode)->value('id');
    }

    private function alreadyPosted(SlipGaji $slip): bool
    {
        return JurnalUmum::query()
            ->where('jenis_referensi', self::JENIS)
            ->where('referensi_id', $slip->getKey())
            ->exists();
    }
}
