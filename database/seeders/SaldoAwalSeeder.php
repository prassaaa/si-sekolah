<?php

namespace Database\Seeders;

use App\Models\Akun;
use App\Models\SaldoAwal;
use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class SaldoAwalSeeder extends Seeder
{
    public function run(): void
    {
        $tahunAjaran = TahunAjaran::where('is_active', true)->first();
        if (! $tahunAjaran) {
            $this->command->warn('Tidak ada tahun ajaran aktif.');

            return;
        }

        $akuns = Akun::where('tipe', 'aset')->where('is_active', true)->get();

        if ($akuns->isEmpty()) {
            $this->command->warn('Tidak ada akun aset.');

            return;
        }

        $saldos = [
            '1-1001' => 50000000, // Kas
            '1-1002' => 100000000, // Bank BCA
            '1-1003' => 75000000, // Bank Mandiri
            '1-1004' => 25000000, // Bank BSI
        ];

        foreach ($akuns as $akun) {
            $saldo = $saldos[$akun->kode] ?? 10000000;
            SaldoAwal::firstOrCreate(
                ['akun_id' => $akun->id, 'tahun_ajaran_id' => $tahunAjaran->id],
                [
                    'saldo' => $saldo,
                    'tanggal' => $tahunAjaran->tanggal_mulai ?? now()->startOfYear(),
                    'keterangan' => 'Saldo awal '.$akun->nama,
                ]
            );
        }

        $this->command->info('Saldo Awal seeded successfully');
    }
}
