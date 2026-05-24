<?php

namespace Database\Seeders;

use App\Models\KartuRfid;
use App\Models\Pegawai;
use App\Models\Siswa;
use Illuminate\Database\Seeder;

class KartuRfidSeeder extends Seeder
{
    public function run(): void
    {
        $siswas = Siswa::query()->limit(50)->get();

        foreach ($siswas as $index => $siswa) {
            KartuRfid::firstOrCreate(
                ['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'status' => 'aktif'],
                [
                    'uid' => sprintf('04%06X', 1000 + $index),
                    'diaktifkan_pada' => now()->subDays(rand(1, 90)),
                ]
            );
        }

        $pegawais = Pegawai::query()->limit(15)->get();

        foreach ($pegawais as $index => $pegawai) {
            KartuRfid::firstOrCreate(
                ['owner_type' => Pegawai::class, 'owner_id' => $pegawai->id, 'status' => 'aktif'],
                [
                    'uid' => sprintf('05%06X', 2000 + $index),
                    'diaktifkan_pada' => now()->subDays(rand(1, 60)),
                ]
            );
        }
    }
}
