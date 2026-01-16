<?php

namespace Database\Seeders;

use App\Models\JabatanPegawai;
use Illuminate\Database\Seeder;

class JabatanPegawaiSeeder extends Seeder
{
    public function run(): void
    {
        $jabatans = [
            [
                'kode' => 'KS001',
                'nama' => 'Kepala Sekolah',
                'jenis' => 'Struktural',
                'golongan' => 'IV',
                'gaji_pokok' => 8000000,
                'tunjangan' => 3000000,
                'deskripsi' => 'Pimpinan tertinggi di sekolah',
                'urutan' => 1,
            ],
            [
                'kode' => 'WKS01',
                'nama' => 'Wakil Kepala Sekolah Kurikulum',
                'jenis' => 'Struktural',
                'golongan' => 'IV',
                'gaji_pokok' => 6000000,
                'tunjangan' => 2000000,
                'deskripsi' => 'Wakil kepala sekolah bidang kurikulum',
                'urutan' => 2,
            ],
            [
                'kode' => 'WKS02',
                'nama' => 'Wakil Kepala Sekolah Kesiswaan',
                'jenis' => 'Struktural',
                'golongan' => 'IV',
                'gaji_pokok' => 6000000,
                'tunjangan' => 2000000,
                'deskripsi' => 'Wakil kepala sekolah bidang kesiswaan',
                'urutan' => 3,
            ],
            [
                'kode' => 'WKS03',
                'nama' => 'Wakil Kepala Sekolah Sarana Prasarana',
                'jenis' => 'Struktural',
                'golongan' => 'IV',
                'gaji_pokok' => 6000000,
                'tunjangan' => 2000000,
                'deskripsi' => 'Wakil kepala sekolah bidang sarana prasarana',
                'urutan' => 4,
            ],
            [
                'kode' => 'WKS04',
                'nama' => 'Wakil Kepala Sekolah Humas',
                'jenis' => 'Struktural',
                'golongan' => 'IV',
                'gaji_pokok' => 6000000,
                'tunjangan' => 2000000,
                'deskripsi' => 'Wakil kepala sekolah bidang hubungan masyarakat',
                'urutan' => 5,
            ],
            [
                'kode' => 'GR001',
                'nama' => 'Guru',
                'jenis' => 'Fungsional',
                'golongan' => 'III',
                'gaji_pokok' => 4500000,
                'tunjangan' => 1500000,
                'deskripsi' => 'Tenaga pendidik',
                'urutan' => 10,
            ],
            [
                'kode' => 'GBK01',
                'nama' => 'Guru BK',
                'jenis' => 'Fungsional',
                'golongan' => 'III',
                'gaji_pokok' => 4500000,
                'tunjangan' => 1500000,
                'deskripsi' => 'Guru bimbingan konseling',
                'urutan' => 11,
            ],
            [
                'kode' => 'WK001',
                'nama' => 'Wali Kelas',
                'jenis' => 'Fungsional',
                'golongan' => 'III',
                'gaji_pokok' => 4500000,
                'tunjangan' => 2000000,
                'deskripsi' => 'Guru yang merangkap sebagai wali kelas',
                'urutan' => 12,
            ],
            [
                'kode' => 'TU001',
                'nama' => 'Kepala Tata Usaha',
                'jenis' => 'Struktural',
                'golongan' => 'III',
                'gaji_pokok' => 5000000,
                'tunjangan' => 1500000,
                'deskripsi' => 'Kepala bagian tata usaha',
                'urutan' => 20,
            ],
            [
                'kode' => 'TU002',
                'nama' => 'Staff Tata Usaha',
                'jenis' => 'Non-Fungsional',
                'golongan' => 'II',
                'gaji_pokok' => 3500000,
                'tunjangan' => 1000000,
                'deskripsi' => 'Staff administrasi tata usaha',
                'urutan' => 21,
            ],
            [
                'kode' => 'BND01',
                'nama' => 'Bendahara',
                'jenis' => 'Struktural',
                'golongan' => 'III',
                'gaji_pokok' => 5000000,
                'tunjangan' => 1500000,
                'deskripsi' => 'Pengelola keuangan sekolah',
                'urutan' => 22,
            ],
            [
                'kode' => 'PRP01',
                'nama' => 'Petugas Perpustakaan',
                'jenis' => 'Non-Fungsional',
                'golongan' => 'II',
                'gaji_pokok' => 3000000,
                'tunjangan' => 800000,
                'deskripsi' => 'Pengelola perpustakaan',
                'urutan' => 30,
            ],
            [
                'kode' => 'LAB01',
                'nama' => 'Petugas Laboratorium',
                'jenis' => 'Non-Fungsional',
                'golongan' => 'II',
                'gaji_pokok' => 3000000,
                'tunjangan' => 800000,
                'deskripsi' => 'Pengelola laboratorium',
                'urutan' => 31,
            ],
            [
                'kode' => 'SAT01',
                'nama' => 'Satpam',
                'jenis' => 'Non-Fungsional',
                'golongan' => 'Non-PNS',
                'gaji_pokok' => 2500000,
                'tunjangan' => 500000,
                'deskripsi' => 'Petugas keamanan sekolah',
                'urutan' => 40,
            ],
            [
                'kode' => 'KBR01',
                'nama' => 'Petugas Kebersihan',
                'jenis' => 'Non-Fungsional',
                'golongan' => 'Non-PNS',
                'gaji_pokok' => 2200000,
                'tunjangan' => 400000,
                'deskripsi' => 'Petugas kebersihan sekolah',
                'urutan' => 41,
            ],
        ];

        foreach ($jabatans as $jabatan) {
            JabatanPegawai::firstOrCreate(
                ['kode' => $jabatan['kode']],
                array_merge($jabatan, ['is_active' => true])
            );
        }
    }
}
