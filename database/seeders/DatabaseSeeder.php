<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Core
            RoleSeeder::class,
            UserSeeder::class,
            SekolahSeeder::class,
            InformasiSeeder::class,

            // Kepegawaian
            JabatanPegawaiSeeder::class,

            // Akademik - Master Data
            TahunAjaranSeeder::class,
            SemesterSeeder::class,
            MataPelajaranSeeder::class,
            JamPelajaranSeeder::class,

            // Kepegawaian - Data Pegawai (setelah Jabatan)
            PegawaiSeeder::class,

            // Akademik - Kelas & Jadwal
            KelasSeeder::class,
            JadwalPelajaranSeeder::class,

            // Kesiswaan - Data Siswa
            SiswaSeeder::class,
            TahfidzSeeder::class,
            IzinKeluarSeeder::class,
            IzinPulangSeeder::class,
            PrestasiSeeder::class,
            PelanggaranSeeder::class,
            KonselingSeeder::class,
            KenaikanKelasSeeder::class,
            KelulusanSeeder::class,

            // Keuangan - Master Data
            AkunSeeder::class,
            KategoriPembayaranSeeder::class,
            JenisPembayaranSeeder::class,
            PosBayarSeeder::class,
            PembayaranPaketSeeder::class,
            PajakSeeder::class,
            UnitPosSeeder::class,
            SettingGajiSeeder::class,

            // Keuangan - Transaksi
            TagihanSiswaSeeder::class,
            PembayaranSeeder::class,
            TabunganSiswaSeeder::class,
            SaldoAwalSeeder::class,
            KasMasukSeeder::class,
            KasKeluarSeeder::class,
            SlipGajiSeeder::class,
            BuktiTransferSeeder::class,

            // Akuntansi
            JurnalUmumSeeder::class,
        ]);
    }
}
