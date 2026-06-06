<?php

namespace App\Observers;

use App\Models\KasKeluar;
use App\Services\Accounting\KasJournalPoster;

class KasKeluarObserver
{
    public function __construct(private KasJournalPoster $poster) {}

    public function created(KasKeluar $kasKeluar): void
    {
        $this->poster->postKasKeluar($kasKeluar);
    }

    public function updated(KasKeluar $kasKeluar): void
    {
        $this->poster->reverse(KasJournalPoster::JENIS_KAS_KELUAR, $kasKeluar);
        $this->poster->postKasKeluar($kasKeluar);
    }

    public function deleted(KasKeluar $kasKeluar): void
    {
        $this->poster->reverse(KasJournalPoster::JENIS_KAS_KELUAR, $kasKeluar);
    }

    public function restored(KasKeluar $kasKeluar): void
    {
        $this->poster->postKasKeluar($kasKeluar);
    }

    public function forceDeleted(KasKeluar $kasKeluar): void
    {
        $this->poster->reverse(KasJournalPoster::JENIS_KAS_KELUAR, $kasKeluar);
    }
}
