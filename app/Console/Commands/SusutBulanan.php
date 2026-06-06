<?php

namespace App\Console\Commands;

use App\Models\SarprasBarang;
use App\Services\Sarpras\SarprasJournalPoster;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SusutBulanan extends Command
{
    /**
     * @var string
     */
    protected $signature = 'sarpras:susut-bulanan {--periode= : Periode YYYY-MM (default: bulan berjalan)}';

    /**
     * @var string
     */
    protected $description = 'Post monthly depreciation journals for active depreciable sarpras assets (idempotent per asset/period).';

    public function handle(SarprasJournalPoster $poster): int
    {
        $periode = $this->option('periode')
            ? Carbon::createFromFormat('Y-m', $this->option('periode'))->startOfMonth()
            : Carbon::now()->startOfMonth();

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

        $this->info("Selesai. Terposting: {$posted}, dilewati: {$skipped}.");

        return self::SUCCESS;
    }
}
