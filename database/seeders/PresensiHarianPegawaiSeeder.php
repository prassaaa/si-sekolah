<?php

namespace Database\Seeders;

use App\Models\Pegawai;
use App\Models\PresensiHarianPegawai;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class PresensiHarianPegawaiSeeder extends Seeder
{
    public function run(): void
    {
        $pegawais = Pegawai::query()->limit(10)->get();
        $startDate = CarbonImmutable::now()->subDays(7)->startOfDay();

        foreach ($pegawais as $pegawai) {
            for ($i = 0; $i < 7; $i++) {
                $tanggal = $startDate->addDays($i);

                if ($tanggal->isWeekend()) {
                    continue;
                }

                $rand = rand(1, 100);

                if ($rand <= 80) {
                    PresensiHarianPegawai::firstOrCreate(
                        ['pegawai_id' => $pegawai->id, 'tanggal' => $tanggal->toDateString()],
                        [
                            'jam_masuk' => '06:50:00',
                            'jam_pulang' => '15:05:00',
                            'status' => 'hadir',
                            'sumber_masuk' => 'rfid',
                            'sumber_pulang' => 'rfid',
                        ]
                    );
                } elseif ($rand <= 90) {
                    $menit = rand(5, 20);
                    PresensiHarianPegawai::firstOrCreate(
                        ['pegawai_id' => $pegawai->id, 'tanggal' => $tanggal->toDateString()],
                        [
                            'jam_masuk' => sprintf('07:%02d:00', $menit),
                            'jam_pulang' => '15:00:00',
                            'status' => 'terlambat',
                            'sumber_masuk' => 'rfid',
                            'sumber_pulang' => 'rfid',
                            'terlambat_menit' => $menit,
                        ]
                    );
                } elseif ($rand <= 95) {
                    PresensiHarianPegawai::firstOrCreate(
                        ['pegawai_id' => $pegawai->id, 'tanggal' => $tanggal->toDateString()],
                        ['status' => 'cuti', 'sumber_masuk' => 'manual', 'keterangan' => 'Cuti tahunan']
                    );
                } else {
                    PresensiHarianPegawai::firstOrCreate(
                        ['pegawai_id' => $pegawai->id, 'tanggal' => $tanggal->toDateString()],
                        ['status' => 'dinas_luar', 'sumber_masuk' => 'manual', 'keterangan' => 'Dinas luar']
                    );
                }
            }
        }
    }
}
