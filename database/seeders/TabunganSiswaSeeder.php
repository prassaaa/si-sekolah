<?php

namespace Database\Seeders;

use App\Models\Siswa;
use App\Models\TabunganSiswa;
use Illuminate\Database\Seeder;

class TabunganSiswaSeeder extends Seeder
{
    public function run(): void
    {
        $siswas = Siswa::where('status', 'aktif')->take(10)->get();

        if ($siswas->isEmpty()) {
            $this->command->warn('Tidak ada siswa aktif.');

            return;
        }

        $count = 0;
        foreach ($siswas as $siswa) {
            // Setor awal
            TabunganSiswa::firstOrCreate(
                ['siswa_id' => $siswa->id, 'tanggal' => now()->subDays(30)->format('Y-m-d'), 'jenis' => 'setor'],
                [
                    'nominal' => rand(50, 200) * 1000,
                    'saldo' => rand(50, 200) * 1000,
                    'keterangan' => 'Setoran awal',
                ]
            );
            $count++;

            // Setor bulanan
            TabunganSiswa::firstOrCreate(
                ['siswa_id' => $siswa->id, 'tanggal' => now()->subDays(15)->format('Y-m-d'), 'jenis' => 'setor'],
                [
                    'nominal' => rand(20, 100) * 1000,
                    'saldo' => rand(100, 300) * 1000,
                    'keterangan' => 'Setoran bulanan',
                ]
            );
            $count++;
        }

        $this->command->info('Tabungan Siswa seeded successfully: '.$count.' records');
    }
}
