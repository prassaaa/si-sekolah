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
                'nuptk' => '1234567890123456',
                'nama' => 'Dr. Ahmad Fauzi, M.Pd.',
                'jabatan_id' => $jabatans['KS001'] ?? array_values($jabatans)[0],
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '1980-01-01',
                'agama' => 'Islam',
                'alamat' => 'Jl. Pendidikan No. 1, Jakarta Selatan',
                'telepon' => '081234567890',
                'email' => 'kepala.sekolah@sekolah.sch.id',
                'tanggal_masuk' => '2010-01-01',
                'status_kepegawaian' => 'PNS',
                'pendidikan_terakhir' => 'S3',
                'jurusan' => 'Manajemen Pendidikan',
                'universitas' => 'Universitas Indonesia',
                'tahun_lulus' => 2005,
                'no_rekening' => '1234567890',
                'nama_bank' => 'BCA',
                'npwp' => '12.345.678.9-012.000',
                'no_bpjs_kesehatan' => '0001234567890',
                'no_bpjs_ketenagakerjaan' => '12345678901',
                'status_pernikahan' => 'Menikah',
                'jumlah_tanggungan' => 3,
                'is_active' => true,
            ],
            [
                'nip' => '198505152012012002',
                'nuptk' => '2345678901234567',
                'nama' => 'Siti Rahmawati, S.Pd.',
                'jabatan_id' => $jabatans['GMP01'] ?? array_values($jabatans)[0],
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '1985-05-15',
                'agama' => 'Islam',
                'alamat' => 'Jl. Guru No. 10, Bandung',
                'telepon' => '081234567891',
                'email' => 'siti.rahmawati@sekolah.sch.id',
                'tanggal_masuk' => '2012-01-01',
                'status_kepegawaian' => 'PNS',
                'pendidikan_terakhir' => 'S1',
                'jurusan' => 'Pendidikan Matematika',
                'universitas' => 'Universitas Pendidikan Indonesia',
                'tahun_lulus' => 2008,
                'no_rekening' => '2345678901',
                'nama_bank' => 'Mandiri',
                'npwp' => '23.456.789.0-123.000',
                'no_bpjs_kesehatan' => '0002345678901',
                'no_bpjs_ketenagakerjaan' => '23456789012',
                'status_pernikahan' => 'Menikah',
                'jumlah_tanggungan' => 2,
                'is_active' => true,
            ],
            [
                'nip' => '199001012015011003',
                'nuptk' => '3456789012345678',
                'nama' => 'Budi Santoso, S.Pd.',
                'jabatan_id' => $jabatans['GMP01'] ?? array_values($jabatans)[0],
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Surabaya',
                'tanggal_lahir' => '1990-01-01',
                'agama' => 'Islam',
                'alamat' => 'Jl. Pahlawan No. 5, Surabaya',
                'telepon' => '081234567892',
                'email' => 'budi.santoso@sekolah.sch.id',
                'tanggal_masuk' => '2015-01-01',
                'status_kepegawaian' => 'GTY',
                'pendidikan_terakhir' => 'S1',
                'jurusan' => 'Pendidikan Bahasa Indonesia',
                'universitas' => 'Universitas Negeri Surabaya',
                'tahun_lulus' => 2012,
                'no_rekening' => '3456789012',
                'nama_bank' => 'BRI',
                'npwp' => '34.567.890.1-234.000',
                'no_bpjs_kesehatan' => '0003456789012',
                'no_bpjs_ketenagakerjaan' => '34567890123',
                'status_pernikahan' => 'Menikah',
                'jumlah_tanggungan' => 1,
                'is_active' => true,
            ],
            [
                'nip' => '199205202018012004',
                'nuptk' => '4567890123456789',
                'nama' => 'Dewi Lestari, S.Pd.',
                'jabatan_id' => $jabatans['GMP01'] ?? array_values($jabatans)[0],
                'jenis_kelamin' => 'P',
                'tempat_lahir' => 'Yogyakarta',
                'tanggal_lahir' => '1992-05-20',
                'agama' => 'Islam',
                'alamat' => 'Jl. Malioboro No. 20, Yogyakarta',
                'telepon' => '081234567893',
                'email' => 'dewi.lestari@sekolah.sch.id',
                'tanggal_masuk' => '2018-01-01',
                'status_kepegawaian' => 'GTT',
                'pendidikan_terakhir' => 'S1',
                'jurusan' => 'Pendidikan IPA',
                'universitas' => 'Universitas Negeri Yogyakarta',
                'tahun_lulus' => 2015,
                'no_rekening' => '4567890123',
                'nama_bank' => 'BSI',
                'npwp' => '45.678.901.2-345.000',
                'no_bpjs_kesehatan' => '0004567890123',
                'no_bpjs_ketenagakerjaan' => '45678901234',
                'status_pernikahan' => 'Belum Menikah',
                'jumlah_tanggungan' => 0,
                'is_active' => true,
            ],
            [
                'nip' => '199510102020011005',
                'nuptk' => null,
                'nama' => 'Andi Wijaya, S.Kom.',
                'jabatan_id' => $jabatans['TU001'] ?? array_values($jabatans)[0],
                'jenis_kelamin' => 'L',
                'tempat_lahir' => 'Semarang',
                'tanggal_lahir' => '1995-10-10',
                'agama' => 'Islam',
                'alamat' => 'Jl. Teknologi No. 15, Semarang',
                'telepon' => '081234567894',
                'email' => 'andi.wijaya@sekolah.sch.id',
                'tanggal_masuk' => '2020-01-01',
                'status_kepegawaian' => 'PTY',
                'pendidikan_terakhir' => 'S1',
                'jurusan' => 'Teknik Informatika',
                'universitas' => 'Universitas Diponegoro',
                'tahun_lulus' => 2018,
                'no_rekening' => '5678901234',
                'nama_bank' => 'BNI',
                'npwp' => '56.789.012.3-456.000',
                'no_bpjs_kesehatan' => '0005678901234',
                'no_bpjs_ketenagakerjaan' => '56789012345',
                'status_pernikahan' => 'Belum Menikah',
                'jumlah_tanggungan' => 0,
                'is_active' => true,
            ],
        ];

        foreach ($pegawais as $pegawai) {
            Pegawai::firstOrCreate(['nip' => $pegawai['nip']], $pegawai);
        }

        $this->command->info('Pegawai seeded successfully: '.count($pegawais).' records');
    }
}
