<?php

use App\Models\Kelas;
use App\Models\Ruangan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Idempotent: skips rows where ruangan_id is already set or ruangan string is empty.
     */
    public function up(): void
    {
        Kelas::query()
            ->whereNull('ruangan_id')
            ->whereNotNull('ruangan')
            ->where('ruangan', '!=', '')
            ->lazyById()
            ->each(function (Kelas $kelas): void {
                $nama = trim($kelas->ruangan);

                if ($nama === '') {
                    return;
                }

                $ruangan = Ruangan::firstOrCreate(
                    ['nama' => $nama],
                    [
                        'kode' => $this->generateUniqueKode($nama),
                        'jenis' => 'kelas',
                        'is_active' => true,
                    ]
                );

                $kelas->ruangan_id = $ruangan->id;
                $kelas->saveQuietly();
            });
    }

    /**
     * No-op: data migration is not reversible without losing information.
     */
    public function down(): void
    {
        //
    }

    /**
     * Generate a unique kode for a new Ruangan based on its nama.
     */
    private function generateUniqueKode(string $nama): string
    {
        $base = 'R-'.Str::upper(Str::slug($nama));
        $kode = $base;
        $counter = 1;

        while (Ruangan::withTrashed()->where('kode', $kode)->exists()) {
            $kode = $base.'-'.$counter;
            $counter++;
        }

        return $kode;
    }
};
