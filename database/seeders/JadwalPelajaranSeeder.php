<?php

namespace Database\Seeders;

use App\Models\JadwalPelajaran;
use App\Models\JamPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pegawai;
use App\Models\Semester;
use Illuminate\Database\Seeder;

class JadwalPelajaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $semester = Semester::where('is_active', true)->first();

        if (! $semester) {
            $this->command->warn('Tidak ada semester aktif. Jalankan SemesterSeeder terlebih dahulu.');

            return;
        }

        $kelasAktif = Kelas::where('is_active', true)->get();

        if ($kelasAktif->isEmpty()) {
            $this->command->warn('Tidak ada kelas aktif. Jalankan KelasSeeder terlebih dahulu.');

            return;
        }

        $mataPelajarans = MataPelajaran::where('is_active', true)->get();
        $jamPelajarans = JamPelajaran::where('is_active', true)
            ->where('jenis', 'Reguler')
            ->orderBy('jam_ke')
            ->get();
        $gurus = Pegawai::whereHas('jabatan', function ($q) {
            $q->where('nama', 'like', '%Guru%');
        })->pluck('id')->toArray();

        if ($mataPelajarans->isEmpty() || $jamPelajarans->isEmpty()) {
            $this->command->warn('Tidak ada mata pelajaran atau jam pelajaran aktif.');

            return;
        }

        $hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        foreach ($kelasAktif as $kelas) {
            // Skip jika sudah ada jadwal
            if (JadwalPelajaran::where('semester_id', $semester->id)
                ->where('kelas_id', $kelas->id)
                ->exists()) {
                continue;
            }

            $mapelIndex = 0;

            foreach ($hari as $hariItem) {
                foreach ($jamPelajarans as $jam) {
                    $mapel = $mataPelajarans[$mapelIndex % $mataPelajarans->count()];
                    $guruId = ! empty($gurus) ? $gurus[array_rand($gurus)] : null;

                    JadwalPelajaran::create([
                        'semester_id' => $semester->id,
                        'kelas_id' => $kelas->id,
                        'mata_pelajaran_id' => $mapel->id,
                        'jam_pelajaran_id' => $jam->id,
                        'guru_id' => $guruId,
                        'hari' => $hariItem,
                        'is_active' => true,
                    ]);

                    $mapelIndex++;
                }
            }

            $this->command->info("Jadwal created for {$kelas->nama}");
        }

        $this->command->info('JadwalPelajaran seeded successfully');
    }
}
