<?php

namespace Database\Seeders;

use App\Models\JamPelajaran;
use Illuminate\Database\Seeder;

class JamPelajaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jamPelajarans = [
            ['jam_ke' => 1, 'waktu_mulai' => '07:00:00', 'waktu_selesai' => '07:45:00', 'durasi' => 45, 'jenis' => 'Reguler'],
            ['jam_ke' => 2, 'waktu_mulai' => '07:45:00', 'waktu_selesai' => '08:30:00', 'durasi' => 45, 'jenis' => 'Reguler'],
            ['jam_ke' => 3, 'waktu_mulai' => '08:30:00', 'waktu_selesai' => '09:15:00', 'durasi' => 45, 'jenis' => 'Reguler'],
            ['jam_ke' => 4, 'waktu_mulai' => '09:15:00', 'waktu_selesai' => '09:30:00', 'durasi' => 15, 'jenis' => 'Istirahat', 'keterangan' => 'Istirahat 1'],
            ['jam_ke' => 5, 'waktu_mulai' => '09:30:00', 'waktu_selesai' => '10:15:00', 'durasi' => 45, 'jenis' => 'Reguler'],
            ['jam_ke' => 6, 'waktu_mulai' => '10:15:00', 'waktu_selesai' => '11:00:00', 'durasi' => 45, 'jenis' => 'Reguler'],
            ['jam_ke' => 7, 'waktu_mulai' => '11:00:00', 'waktu_selesai' => '11:45:00', 'durasi' => 45, 'jenis' => 'Reguler'],
            ['jam_ke' => 8, 'waktu_mulai' => '11:45:00', 'waktu_selesai' => '12:30:00', 'durasi' => 45, 'jenis' => 'Istirahat', 'keterangan' => 'Istirahat/Sholat Dzuhur'],
            ['jam_ke' => 9, 'waktu_mulai' => '12:30:00', 'waktu_selesai' => '13:15:00', 'durasi' => 45, 'jenis' => 'Reguler'],
            ['jam_ke' => 10, 'waktu_mulai' => '13:15:00', 'waktu_selesai' => '14:00:00', 'durasi' => 45, 'jenis' => 'Reguler'],
            ['jam_ke' => 11, 'waktu_mulai' => '14:00:00', 'waktu_selesai' => '14:45:00', 'durasi' => 45, 'jenis' => 'Reguler'],
            ['jam_ke' => 12, 'waktu_mulai' => '14:45:00', 'waktu_selesai' => '15:30:00', 'durasi' => 45, 'jenis' => 'Ekstrakurikuler', 'keterangan' => 'Ekstrakurikuler'],
        ];

        foreach ($jamPelajarans as $data) {
            JamPelajaran::firstOrCreate(
                ['jam_ke' => $data['jam_ke'], 'jenis' => $data['jenis']],
                array_merge($data, ['is_active' => true])
            );
        }
    }
}
