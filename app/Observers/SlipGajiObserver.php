<?php

namespace App\Observers;

use App\Models\SlipGaji;
use App\Services\Accounting\SlipGajiJournalPoster;
use Illuminate\Support\Facades\DB;

/**
 * Menjaga jurnal akrual gaji tetap sinkron dengan siklus hidup slip.
 *
 * Posting akrual itu sendiri dipicu eksplisit lewat SlipGaji::approve(); observer
 * ini hanya menangani penghapusan/pemulihan agar jurnal (dan KasKeluar pembayaran)
 * ikut ter-reverse / ter-repost secara idempoten.
 */
class SlipGajiObserver
{
    public function __construct(private SlipGajiJournalPoster $poster) {}

    /**
     * Saat slip dihapus: reverse jurnal akrual, lalu hapus KasKeluar pembayaran
     * terkait (jika ada). Menghapus KasKeluar otomatis mereverse jurnal kas
     * keluar lewat KasKeluarObserver.
     */
    public function deleted(SlipGaji $slip): void
    {
        DB::transaction(function () use ($slip): void {
            $this->poster->reverseAkrual($slip);

            if ($slip->kas_keluar_id !== null) {
                $slip->kasKeluar()->first()?->delete();
            }
        });
    }

    /**
     * Saat slip dipulihkan: re-post akrual bila slip masih approved/paid.
     * Poster bersifat idempoten sehingga aman bila jurnal sudah ada.
     */
    public function restored(SlipGaji $slip): void
    {
        $this->poster->postAkrual($slip);
    }

    public function forceDeleted(SlipGaji $slip): void
    {
        DB::transaction(function () use ($slip): void {
            $this->poster->reverseAkrual($slip);

            if ($slip->kas_keluar_id !== null) {
                $slip->kasKeluar()->first()?->delete();
            }
        });
    }
}
