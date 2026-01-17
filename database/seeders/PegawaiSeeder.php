<?php

namespace Database\Seeders;

use App\Models\JabatanPegawai;
use App\Models\Pegawai;
use Illuminate\Database\Seeder;

class PegawaiSeeder extends Seeder
{
    public function run(): void
    {
        $jabatans = JabatanPegawai::pluck('id', 'kode')->toArray();

        if (empty($jabatans)) {
            $this->command->warn('Tidak ada jabatan. Jalankan JabatanPegawaiSeeder terlebih dahulu.');

            return;
        }

        $pegawais = [
            [
                'nip' => '198001012010011001',
                'nama' => 'Dr. Ahmad Fauzi, M.Pd.',
                'jabatan_id' => $jabatans['KS001'] ?? array_values($jabatans)[0],
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '1980-01-01',
                'alamat' => 'Jl. Pendidikan No. 1',
                'telepon' => '081234567890',
                'email' => 'kepala.sekolah@sekolah.sch.id',
                'tanggal_masuk' => '2010-01-01',
                'status_kepegawaian' => 'PNS',
                'is_active' => true,
            ],
            [
                'nip' => '198505152012012002',
                'nama' => 'Siti Rahmawati, S.Pd.',
                'jabatan_id' => $jabatans['GMP01'] ?? array_values($jabatans)[0],
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '1985-05-15',
                'alamat' => 'Jl. Guru No. 10',
                'telepon' => '081234567891',
                'email' => 'siti.rahmawati@sekolah.sch.id',
                'tanggal_masuk' => '2012-01-01',
                'status_kepegawaian' => 'PNS',
                'is_active' => true,
            ],
            [
                'nip' => '199001012015011003',
                'nama' => 'Budi Santoso, S.Pd.',
                'jabatan_id' => $jabatans['GMP01'] ?? array_values($jabatans)[0],
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Surabaya',
                'tanggal_lahir' => '1990-01-01',
                'alamat' => 'Jl. Pahlawan No. 5',
                'telepon' => '081234567892',
                'email' => 'budi.santoso@sekolah.sch.id',
                'tanggal_masuk' => '2015-01-01',
                'status_kepegawaian' => 'GTY',
                'is_active' => true,
            ],
            [
                'nip' => '199205202018012004',
                'nama' => 'Dewi Lestari, S.Pd.',
                'jabatan_id' => $jabatans['GMP01'] ?? array_values($jabatans)[0],
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Yogyakarta',
                'tanggal_lahir' => '1992-05-20',
                'alamat' => 'Jl. Malioboro No. 20',
                'telepon' => '081234567893',
                'email' => 'dewi.lestari@sekolah.sch.id',
                'tanggal_masuk' => '2018-01-01',
                'status_kepegawaian' => 'GTT',
                'is_active' => true,
            ],
            [
                'nip' => '199510102020011005',
                'nama' => 'Andi Wijaya, S.Kom.',
                'jabatan_id' => $jabatans['TU001'] ?? array_values($jabatans)[0],
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Semarang',
                'tanggal_lahir' => '1995-10-10',
                'alamat' => 'Jl. Teknologi No. 15',
                'telepon' => '081234567894',
                'email' => 'andi.wijaya@sekolah.sch.id',
                'tanggal_masuk' => '2020-01-01',
                'status_kepegawaian' => 'PTY',
                'is_active' => true,
            ],
        ];

        foreach ($pegawais as $pegawai) {
            Pegawai::firstOrCreate(['nip' => $pegawai['nip']], $pegawai);
        }

        $this->command->info('Pegawai seeded successfully: '.count($pegawais).' records');
    }
}
