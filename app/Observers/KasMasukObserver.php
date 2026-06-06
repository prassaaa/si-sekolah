<?php

namespace App\Observers;

use App\Models\KasMasuk;
use App\Services\Accounting\KasJournalPoster;

class KasMasukObserver
{
    public function __construct(private KasJournalPoster $poster) {}

    public function created(KasMasuk $kasMasuk): void
    {
        $this->poster->postKasMasuk($kasMasuk);
    }

    public function updated(KasMasuk $kasMasuk): void
    {
        $this->poster->reverse(KasJournalPoster::JENIS_KAS_MASUK, $kasMasuk);
        $this->poster->postKasMasuk($kasMasuk);
    }

    public function deleted(KasMasuk $kasMasuk): void
    {
        $this->poster->reverse(KasJournalPoster::JENIS_KAS_MASUK, $kasMasuk);
    }

    public function restored(KasMasuk $kasMasuk): void
    {
        $this->poster->postKasMasuk($kasMasuk);
    }

    public function forceDeleted(KasMasuk $kasMasuk): void
    {
        $this->poster->reverse(KasJournalPoster::JENIS_KAS_MASUK, $kasMasuk);
    }
}
