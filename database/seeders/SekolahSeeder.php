<?php

namespace Database\Seeders;

use App\Models\Sekolah;
use Illuminate\Database\Seeder;

class SekolahSeeder extends Seeder
{
    public function run(): void
    {
        Sekolah::firstOrCreate(
            ['npsn' => '12345678'],
            [
                'nama' => 'SMP Islam Terpadu',
                'nama_yayasan' => 'Yayasan Pendidikan Islam',
                'jenjang' => 'SMP',
                'status' => 'Swasta',
                'alamat' => 'Jl. Pendidikan No. 1',
                'kelurahan' => 'Sukamaju',
                'kecamatan' => 'Ciputat',
                'kabupaten' => 'Tangerang Selatan',
                'provinsi' => 'Banten',
                'kode_pos' => '15411',
                'telepon' => '021-12345678',
                'email' => 'info@smpit.sch.id',
                'website' => 'https://smpit.sch.id',
                'kepala_sekolah' => 'H. Ahmad Fauzi, S.Pd., M.Pd.',
                'nip_kepala_sekolah' => '196501011990031001',
                'visi' => 'Menjadi sekolah unggulan yang melahirkan generasi beriman, berilmu, dan berakhlak mulia.',
                'misi' => "1. Menyelenggarakan pendidikan berbasis Al-Quran\n2. Mengembangkan potensi akademik dan non-akademik siswa\n3. Membentuk karakter islami pada seluruh warga sekolah",
                'tahun_berdiri' => 2010,
                'akreditasi' => 'A',
                'tanggal_akreditasi' => '2023-05-15',
                'no_sk_operasional' => 'SK/420/DIKNAS/2010',
                'tanggal_sk_operasional' => '2010-07-01',
                'is_active' => true,
            ]
        );
    }
}
