<?php

namespace Database\Seeders;

use App\Models\Absensi;
use App\Models\JadwalPelajaran;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AbsensiSeeder extends Seeder
{
    public function run(): void
    {
        $jadwals = JadwalPelajaran::where('is_active', true)
            ->with('kelas')
            ->get();

        if ($jadwals->isEmpty()) {
            $this->command->warn('Tidak ada jadwal pelajaran aktif. Jalankan JadwalPelajaranSeeder terlebih dahulu.');

            return;
        }

        if (Absensi::exists()) {
            $this->command->warn('Data absensi sudah ada, skip seeding.');

            return;
        }

        $hariMap = [
            'Senin' => Carbon::MONDAY,
            'Selasa' => Carbon::TUESDAY,
            'Rabu' => Carbon::WEDNESDAY,
            'Kamis' => Carbon::THURSDAY,
            'Jumat' => Carbon::FRIDAY,
            'Sabtu' => Carbon::SATURDAY,
        ];

        $startDate = Carbon::now()->subWeeks(4)->startOfWeek();
        $endDate = Carbon::now()->subDay();

        foreach ($jadwals as $jadwal) {
            $siswaIds = Siswa::where('kelas_id', $jadwal->kelas_id)
                ->where('is_active', true)
                ->pluck('id');

            if ($siswaIds->isEmpty()) {
                continue;
            }

            $dayOfWeek = $hariMap[$jadwal->hari] ?? null;

            if ($dayOfWeek === null) {
                continue;
            }

            $currentDate = $startDate->copy();

            while ($currentDate->lte($endDate)) {
                if ($currentDate->dayOfWeekIso === $dayOfWeek) {
                    $rows = [];

                    foreach ($siswaIds as $siswaId) {
                        $rand = fake()->numberBetween(1, 100);

                        if ($rand <= 85) {
                            $status = 'hadir';
                            $keterangan = null;
                        } elseif ($rand <= 92) {
                            $status = 'sakit';
                            $keterangan = fake()->randomElement([
                                'Demam',
                                'Flu',
                                'Sakit perut',
                                'Sakit kepala',
                            ]);
                        } elseif ($rand <= 97) {
                            $status = 'izin';
                            $keterangan = fake()->randomElement([
                                'Acara keluarga',
                                'Keperluan mendadak',
                                'Kontrol ke dokter',
                            ]);
                        } else {
                            $status = 'alpha';
                            $keterangan = null;
                        }

                        $rows[] = [
                            'jadwal_pelajaran_id' => $jadwal->id,
                            'siswa_id' => $siswaId,
                            'tanggal' => $currentDate->toDateString(),
                            'status' => $status,
                            'keterangan' => $keterangan,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    Absensi::insert($rows);
                }

                $currentDate->addDay();
            }
        }

        $this->command->info('AbsensiSeeder seeded successfully');
    }
}
