<?php

namespace App\Observers;

use App\Models\Pembayaran;
use App\Services\Accounting\PembayaranJournalPoster;
use Illuminate\Support\Facades\DB;

class PembayaranObserver
{
    public function __construct(private PembayaranJournalPoster $poster) {}

    public function created(Pembayaran $pembayaran): void
    {
        $this->poster->post($pembayaran);
    }

    /**
     * Reverse lalu repost dalam satu transaction agar tidak ada jeda di mana
     * jurnal tidak ada atau jurnal ganda muncul. Hanya bereaksi ketika atribut
     * yang memengaruhi jurnal berubah (status, nominal, tanggal, akun sumber).
     */
    public function updated(Pembayaran $pembayaran): void
    {
        if (! $pembayaran->wasChanged([
            'status',
            'jumlah_bayar',
            'tanggal_bayar',
            'unit_pos_id',
            'tagihan_siswa_id',
        ])) {
            return;
        }

        DB::transaction(function () use ($pembayaran): void {
            $this->poster->reverse($pembayaran);
            $this->poster->post($pembayaran);
        });
    }

    public function deleted(Pembayaran $pembayaran): void
    {
        $this->poster->reverse($pembayaran);
    }

    public function restored(Pembayaran $pembayaran): void
    {
        $this->poster->post($pembayaran);
    }

    public function forceDeleted(Pembayaran $pembayaran): void
    {
        $this->poster->reverse($pembayaran);
    }
}
