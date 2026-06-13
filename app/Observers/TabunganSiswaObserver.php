<?php

namespace App\Observers;

use App\Models\TabunganSiswa;
use App\Services\Accounting\TabunganJournalPoster;
use Illuminate\Support\Facades\DB;

class TabunganSiswaObserver
{
    public function __construct(private TabunganJournalPoster $poster) {}

    public function created(TabunganSiswa $tabungan): void
    {
        $this->poster->post($tabungan);
    }

    /**
     * Reverse lalu repost dalam satu transaction agar tidak ada jeda di mana
     * jurnal tidak ada atau jurnal ganda muncul. Hanya jika atribut yang
     * memengaruhi jurnal (jenis/nominal/tanggal/siswa_id) berubah.
     */
    public function updated(TabunganSiswa $tabungan): void
    {
        if (! $tabungan->wasChanged(['jenis', 'nominal', 'tanggal', 'siswa_id'])) {
            return;
        }

        DB::transaction(function () use ($tabungan): void {
            $this->poster->reverse(TabunganJournalPoster::JENIS, $tabungan);
            $this->poster->post($tabungan);
        });
    }

    public function deleted(TabunganSiswa $tabungan): void
    {
        $this->poster->reverse(TabunganJournalPoster::JENIS, $tabungan);
    }

    public function restored(TabunganSiswa $tabungan): void
    {
        $this->poster->post($tabungan);
    }

    public function forceDeleted(TabunganSiswa $tabungan): void
    {
        $this->poster->reverse(TabunganJournalPoster::JENIS, $tabungan);
    }
}
