<?php

namespace App\Console\Commands;

use App\Models\JurnalUmum;
use App\Models\SarprasBarang;
use App\Services\Sarpras\SarprasJournalPoster;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SusutBulanan extends Command
{
    /**
     * @var string
     */
    protected $signature = 'sarpras:susut-bulanan
        {--periode= : Periode tunggal YYYY-MM (memaksa hanya bulan ini; mematikan catch-up)}
        {--no-catchup : Hanya proses bulan berjalan, tanpa mengejar periode terlewat}';

    /**
     * @var string
     */
    protected $description = 'Post monthly depreciation journals for active depreciable sarpras assets (idempotent per asset/period). Tanpa --periode, mengejar setiap bulan yang terlewat sejak posting terakhir s/d bulan berjalan.';

    public function handle(SarprasJournalPoster $poster): int
    {
        $periodes = $this->resolvePeriodes();

        if ($periodes === []) {
            $this->info('Tidak ada periode yang perlu diproses.');

            return self::SUCCESS;
        }

        $totalPosted = 0;
        $totalSkipped = 0;

        foreach ($periodes as $periode) {
            [$posted, $skipped] = $this->prosesPeriode($poster, $periode);
            $totalPosted += $posted;
            $totalSkipped += $skipped;
        }

        $rangeLabel = count($periodes) === 1
            ? $periodes[0]->format('Y-m')
            : $periodes[0]->format('Y-m').' s/d '.end($periodes)->format('Y-m');

        $this->info("Selesai untuk {$rangeLabel}. Total terposting: {$totalPosted}, dilewati: {$totalSkipped}.");

        return self::SUCCESS;
    }

    /**
     * Tentukan daftar periode (awal bulan) yang akan diproses.
     *
     * - Dengan --periode: tepat satu bulan tersebut.
     * - Tanpa --periode (default): dari bulan terlewat pertama s/d bulan berjalan.
     *   Bulan awal = bulan setelah penyusutan terakhir yang sudah terposting;
     *   bila belum ada posting sama sekali, mulai dari bulan cut-off konfigurasi
     *   (tidak pernah membackfill periode sebelum cut-off).
     * - Dengan --no-catchup: hanya bulan berjalan.
     *
     * @return list<Carbon>
     */
    private function resolvePeriodes(): array
    {
        if ($this->option('periode')) {
            return [
                Carbon::createFromFormat('Y-m', $this->option('periode'))->startOfMonth(),
            ];
        }

        $bulanBerjalan = Carbon::now()->startOfMonth();

        if ($this->option('no-catchup')) {
            return [$bulanBerjalan];
        }

        $mulai = $this->periodeMulaiCatchUp($bulanBerjalan);

        if ($mulai->greaterThan($bulanBerjalan)) {
            return [];
        }

        $periodes = [];
        $cursor = $mulai->copy();

        while ($cursor->lessThanOrEqualTo($bulanBerjalan)) {
            $periodes[] = $cursor->copy();
            $cursor->addMonthNoOverflow();
        }

        return $periodes;
    }

    /**
     * Bulan awal catch-up: bulan setelah periode penyusutan terakhir yang
     * terposting; bila belum ada, mulai dari bulan cut-off. Hasil tidak pernah
     * lebih awal dari bulan cut-off.
     */
    private function periodeMulaiCatchUp(Carbon $bulanBerjalan): Carbon
    {
        $bulanCutoff = Carbon::parse(config('akuntansi.cutoff_posting'))->startOfMonth();

        $periodeTerakhir = $this->periodeTerakhirTerposting();

        if ($periodeTerakhir === null) {
            return $bulanCutoff;
        }

        $setelahTerakhir = $periodeTerakhir->copy()->addMonthNoOverflow()->startOfMonth();

        return $setelahTerakhir->greaterThan($bulanCutoff) ? $setelahTerakhir : $bulanCutoff;
    }

    /**
     * Periode (awal bulan) terakhir yang punya jurnal penyusutan, dibaca dari
     * marker `referensi` ber-pola SUSUT-YYYY-MM pada jurnal penyusutan sarpras.
     * Menghormati soft-delete: reversal periode terakhir membuat catch-up
     * mengulang periode tersebut.
     */
    private function periodeTerakhirTerposting(): ?Carbon
    {
        $referensi = JurnalUmum::query()
            ->where('jenis_referensi', SarprasJournalPoster::JENIS_PENYUSUTAN)
            ->where('referensi', 'like', 'SUSUT-%')
            ->orderByDesc('referensi')
            ->value('referensi');

        if ($referensi === null) {
            return null;
        }

        $marker = substr((string) $referensi, strlen('SUSUT-'));

        return Carbon::createFromFormat('Y-m', $marker)->startOfMonth();
    }

    /**
     * Proses satu periode atas seluruh aset tetap aktif yang dapat disusutkan.
     * Idempotensi & batas akumulasi ditangani di SarprasJournalPoster.
     *
     * @return array{0: int, 1: int} [posted, skipped]
     */
    private function prosesPeriode(SarprasJournalPoster $poster, Carbon $periode): array
    {
        $this->info('Posting penyusutan periode '.$periode->format('Y-m').'...');

        $posted = 0;
        $skipped = 0;

        SarprasBarang::query()
            ->with('kategori')
            ->where('tipe', 'aset')
            ->where('is_active', true)
            ->whereNotNull('tanggal_perolehan')
            ->where('metode_susut', '!=', 'tanpa')
            ->chunkById(200, function ($barangs) use ($poster, $periode, &$posted, &$skipped): void {
                foreach ($barangs as $barang) {
                    if ($poster->postPenyusutan($barang, $periode)) {
                        $posted++;
                    } else {
                        $skipped++;
                    }
                }
            });

        return [$posted, $skipped];
    }
}
