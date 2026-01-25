<?php

namespace Database\Seeders;

use App\Models\JenisPembayaran;
use App\Models\KategoriPembayaran;
use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class JenisPembayaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tahunAjaran = TahunAjaran::where('is_active', true)->first();

        if (! $tahunAjaran) {
            $this->command->warn('Tidak ada tahun ajaran aktif. Silakan jalankan TahunAjaranSeeder terlebih dahulu.');

            return;
        }

        // Get kategori pembayaran IDs
        $kategoriSPP = KategoriPembayaran::where('kode', 'SPP')->first()?->id;
        $kategoriUG = KategoriPembayaran::where('kode', 'UG')->first()?->id;
        $kategoriSR = KategoriPembayaran::where('kode', 'SR')->first()?->id;
        $kategoriBK = KategoriPembayaran::where('kode', 'BK')->first()?->id;
        $kategoriKG = KategoriPembayaran::where('kode', 'KG')->first()?->id;
        $kategoriUJ = KategoriPembayaran::where('kode', 'UJ')->first()?->id;
        $kategoriWS = KategoriPembayaran::where('kode', 'WS')->first()?->id;
        $kategoriTB = KategoriPembayaran::where('kode', 'TB')->first()?->id;

        $jenisPembayarans = [
            // SPP Bulanan
            [
                'kategori_pembayaran_id' => $kategoriSPP,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'kode' => 'SPP-7',
                'nama' => 'SPP Kelas 7',
                'nominal' => 350000,
                'jenis' => 'bulanan',
                'deskripsi' => 'SPP bulanan untuk kelas 7',
                'tanggal_jatuh_tempo' => 10,
                'is_active' => true,
            ],
            [
                'kategori_pembayaran_id' => $kategoriSPP,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'kode' => 'SPP-8',
                'nama' => 'SPP Kelas 8',
                'nominal' => 350000,
                'jenis' => 'bulanan',
                'deskripsi' => 'SPP bulanan untuk kelas 8',
                'tanggal_jatuh_tempo' => 10,
                'is_active' => true,
            ],
            [
                'kategori_pembayaran_id' => $kategoriSPP,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'kode' => 'SPP-9',
                'nama' => 'SPP Kelas 9',
                'nominal' => 350000,
                'jenis' => 'bulanan',
                'deskripsi' => 'SPP bulanan untuk kelas 9',
                'tanggal_jatuh_tempo' => 10,
                'is_active' => true,
            ],
            // Uang Gedung (Sekali Bayar)
            [
                'kategori_pembayaran_id' => $kategoriUG,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'kode' => 'UG-7',
                'nama' => 'Uang Gedung Kelas 7',
                'nominal' => 2500000,
                'jenis' => 'sekali_bayar',
                'deskripsi' => 'Uang gedung untuk siswa baru kelas 7',
                'tanggal_jatuh_tempo' => 15,
                'is_active' => true,
            ],
            [
                'kategori_pembayaran_id' => $kategoriUG,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'kode' => 'UG-10',
                'nama' => 'Uang Gedung Kelas 10',
                'nominal' => 3000000,
                'jenis' => 'sekali_bayar',
                'deskripsi' => 'Uang gedung untuk siswa baru kelas 10',
                'tanggal_jatuh_tempo' => 15,
                'is_active' => true,
            ],
            // Seragam (Sekali Bayar)
            [
                'kategori_pembayaran_id' => $kategoriSR,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'kode' => 'SR-PD',
                'nama' => 'Seragam Pendek',
                'nominal' => 500000,
                'jenis' => 'sekali_bayar',
                'deskripsi' => 'Seragam pendek',
                'tanggal_jatuh_tempo' => 20,
                'is_active' => true,
            ],
            [
                'kategori_pembayaran_id' => $kategoriSR,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'kode' => 'SR-PJ',
                'nama' => 'Seragam Panjang',
                'nominal' => 450000,
                'jenis' => 'sekali_bayar',
                'deskripsi' => 'Seragam panjang',
                'tanggal_jatuh_tempo' => 20,
                'is_active' => true,
            ],
            [
                'kategori_pembayaran_id' => $kategoriSR,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'kode' => 'SR-OL',
                'nama' => 'Seragam Olahraga',
                'nominal' => 250000,
                'jenis' => 'sekali_bayar',
                'deskripsi' => 'Seragam olahraga',
                'tanggal_jatuh_tempo' => 20,
                'is_active' => true,
            ],
            // Buku (Tahunan)
            [
                'kategori_pembayaran_id' => $kategoriBK,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'kode' => 'BK-7',
                'nama' => 'Paket Buku Kelas 7',
                'nominal' => 750000,
                'jenis' => 'tahunan',
                'deskripsi' => 'Paket buku pelajaran kelas 7',
                'tanggal_jatuh_tempo' => 25,
                'is_active' => true,
            ],
            [
                'kategori_pembayaran_id' => $kategoriBK,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'kode' => 'BK-8',
                'nama' => 'Paket Buku Kelas 8',
                'nominal' => 750000,
                'jenis' => 'tahunan',
                'deskripsi' => 'Paket buku pelajaran kelas 8',
                'tanggal_jatuh_tempo' => 25,
                'is_active' => true,
            ],
            [
                'kategori_pembayaran_id' => $kategoriBK,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'kode' => 'BK-9',
                'nama' => 'Paket Buku Kelas 9',
                'nominal' => 750000,
                'jenis' => 'tahunan',
                'deskripsi' => 'Paket buku pelajaran kelas 9',
                'tanggal_jatuh_tempo' => 25,
                'is_active' => true,
            ],
            // Kegiatan (Insidental)
            [
                'kategori_pembayaran_id' => $kategoriKG,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'kode' => 'KG-PS',
                'nama' => 'Pramuka',
                'nominal' => 150000,
                'jenis' => 'insidental',
                'deskripsi' => 'Biaya kegiatan Pramuka',
                'tanggal_jatuh_tempo' => 15,
                'is_active' => true,
            ],
            [
                'kategori_pembayaran_id' => $kategoriKG,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'kode' => 'KG-OR',
                'nama' => 'Paskibra',
                'nominal' => 150000,
                'jenis' => 'insidental',
                'deskripsi' => 'Biaya kegiatan Paskibra',
                'tanggal_jatuh_tempo' => 15,
                'is_active' => true,
            ],
            // Ujian
            [
                'kategori_pembayaran_id' => $kategoriUJ,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'kode' => 'UJ-AS',
                'nama' => 'Ujian Akhir Semester',
                'nominal' => 100000,
                'jenis' => 'insidental',
                'deskripsi' => 'Biaya ujian akhir semester',
                'tanggal_jatuh_tempo' => 10,
                'is_active' => true,
            ],
            [
                'kategori_pembayaran_id' => $kategoriUJ,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'kode' => 'UJ-UN',
                'nama' => 'Ujian Nasional',
                'nominal' => 250000,
                'jenis' => 'insidental',
                'deskripsi' => 'Biaya ujian nasional',
                'tanggal_jatuh_tempo' => 10,
                'is_active' => true,
            ],
            // Wisuda
            [
                'kategori_pembayaran_id' => $kategoriWS,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'kode' => 'WS-9',
                'nama' => 'Wisuda Kelas 9',
                'nominal' => 500000,
                'jenis' => 'sekali_bayar',
                'deskripsi' => 'Biaya wisuda kelas 9',
                'tanggal_jatuh_tempo' => 20,
                'is_active' => true,
            ],
            [
                'kategori_pembayaran_id' => $kategoriWS,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'kode' => 'WS-12',
                'nama' => 'Wisuda Kelas 12',
                'nominal' => 750000,
                'jenis' => 'sekali_bayar',
                'deskripsi' => 'Biaya wisuda kelas 12',
                'tanggal_jatuh_tempo' => 20,
                'is_active' => true,
            ],
            // Tabungan (Bulanan)
            [
                'kategori_pembayaran_id' => $kategoriTB,
                'tahun_ajaran_id' => $tahunAjaran->id,
                'kode' => 'TB-WJ',
                'nama' => 'Tabungan Wajib',
                'nominal' => 50000,
                'jenis' => 'bulanan',
                'deskripsi' => 'Tabungan wajib bulanan',
                'tanggal_jatuh_tempo' => 10,
                'is_active' => true,
            ],
        ];

        foreach ($jenisPembayarans as $jenis) {
            JenisPembayaran::firstOrCreate(
                ['kode' => $jenis['kode'], 'tahun_ajaran_id' => $jenis['tahun_ajaran_id']],
                $jenis
            );
        }

        $this->command->info('Jenis Pembayaran seeded successfully: '.count($jenisPembayarans).' records');
    }
}
