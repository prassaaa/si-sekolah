<?php

namespace Database\Seeders;

use App\Models\PresensiHarian;
use App\Models\Siswa;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class PresensiHarianSeeder extends Seeder
{
    public function run(): void
    {
        $siswas = Siswa::query()->limit(20)->get();
        $startDate = CarbonImmutable::now()->subDays(7)->startOfDay();

        foreach ($siswas as $siswa) {
            for ($i = 0; $i < 7; $i++) {
                $tanggal = $startDate->addDays($i);

                if ($tanggal->isWeekend()) {
                    continue;
                }

                $rand = rand(1, 100);

                if ($rand <= 70) {
                    PresensiHarian::firstOrCreate(
                        ['siswa_id' => $siswa->id, 'tanggal' => $tanggal->toDateString()],
                        [
                            'jam_masuk' => '06:55:00',
                            'jam_pulang' => '13:05:00',
                            'status' => 'hadir',
                            'sumber_masuk' => 'rfid',
                            'sumber_pulang' => 'rfid',
                        ]
                    );
                } elseif ($rand <= 85) {
                    $menit = rand(5, 25);
                    PresensiHarian::firstOrCreate(
                        ['siswa_id' => $siswa->id, 'tanggal' => $tanggal->toDateString()],
                        [
                            'jam_masuk' => sprintf('07:%02d:00', $menit),
                            'jam_pulang' => '13:00:00',
                            'status' => 'terlambat',
                            'sumber_masuk' => 'rfid',
                            'sumber_pulang' => 'rfid',
                            'terlambat_menit' => $menit,
                        ]
                    );
                } elseif ($rand <= 92) {
                    PresensiHarian::firstOrCreate(
                        ['siswa_id' => $siswa->id, 'tanggal' => $tanggal->toDateString()],
                        [
                            'status' => 'izin',
                            'sumber_masuk' => 'manual',
                            'keterangan' => 'Izin keluarga',
                        ]
                    );
                } elseif ($rand <= 97) {
                    PresensiHarian::firstOrCreate(
                        ['siswa_id' => $siswa->id, 'tanggal' => $tanggal->toDateString()],
                        [
                            'status' => 'sakit',
                            'sumber_masuk' => 'manual',
                            'keterangan' => 'Surat dokter terlampir',
                        ]
                    );
                } else {
                    PresensiHarian::firstOrCreate(
                        ['siswa_id' => $siswa->id, 'tanggal' => $tanggal->toDateString()],
                        [
                            'status' => 'alpha',
                        ]
                    );
                }
            }
        }
    }
}
