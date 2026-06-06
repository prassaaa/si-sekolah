<?php

namespace Database\Seeders;

use App\Models\Ruangan;
use App\Models\SarprasBarang;
use App\Models\SarprasKategori;
use Illuminate\Database\Seeder;

class SarprasBarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kategoriElk = SarprasKategori::where('kode', 'ELK')->first();
        $kategoriMbl = SarprasKategori::where('kode', 'MBL')->first();
        $kategoriLab = SarprasKategori::where('kode', 'LAB')->first();
        $kategoriOlr = SarprasKategori::where('kode', 'OLR')->first();
        $kategoriKdr = SarprasKategori::where('kode', 'KDR')->first();
        $kategoriMbl2 = $kategoriMbl; // alias for readability below

        if (! $kategoriElk || ! $kategoriMbl || ! $kategoriLab) {
            $this->command->warn('Kategori sarpras belum ada. Jalankan SarprasKategoriSeeder terlebih dahulu.');

            return;
        }

        $ruangKepsek = Ruangan::where('kode', 'KTR-KPS')->first();
        $ruangGuru = Ruangan::where('kode', 'KTR-GRU')->first();
        $ruangTu = Ruangan::where('kode', 'KTR-TU')->first();
        $labIpa = Ruangan::where('kode', 'LAB-IPA')->first();
        $labKom = Ruangan::where('kode', 'LAB-KOM')->first();
        $perpus = Ruangan::where('kode', 'PRP-01')->first();
        $aula = Ruangan::where('kode', 'AUL-01')->first();
        $gudang = Ruangan::where('kode', 'GDG-01')->first();
        $gudangOlr = Ruangan::where('kode', 'GDG-02')->first();

        if (! $ruangGuru) {
            $this->command->warn('Ruangan belum ada. Jalankan RuanganSeeder terlebih dahulu.');

            return;
        }

        $barangs = [
            // --- Elektronik: Aset ---
            [
                'kode_inventaris' => 'INV-ELK-001',
                'nama' => 'Laptop Dell Latitude',
                'sarpras_kategori_id' => $kategoriElk->id,
                'ruangan_id' => $ruangGuru?->id,
                'tipe' => 'aset',
                'merk' => 'Dell',
                'spesifikasi' => 'Intel Core i5, RAM 8GB, SSD 256GB, Windows 11',
                'kondisi' => 'baik',
                'status' => 'tersedia',
                'sumber_dana' => 'bos',
                'tahun_perolehan' => 2022,
                'harga_perolehan' => 8500000,
                'jumlah' => 1,
                'satuan' => 'unit',
            ],
            [
                'kode_inventaris' => 'INV-ELK-002',
                'nama' => 'Laptop Lenovo ThinkPad',
                'sarpras_kategori_id' => $kategoriElk->id,
                'ruangan_id' => $ruangTu?->id,
                'tipe' => 'aset',
                'merk' => 'Lenovo',
                'spesifikasi' => 'Intel Core i7, RAM 16GB, SSD 512GB, Windows 11',
                'kondisi' => 'baik',
                'status' => 'tersedia',
                'sumber_dana' => 'komite',
                'tahun_perolehan' => 2023,
                'harga_perolehan' => 12000000,
                'jumlah' => 1,
                'satuan' => 'unit',
            ],
            [
                'kode_inventaris' => 'INV-ELK-003',
                'nama' => 'Proyektor Epson EB-X41',
                'sarpras_kategori_id' => $kategoriElk->id,
                'ruangan_id' => $ruangGuru?->id,
                'tipe' => 'aset',
                'merk' => 'Epson',
                'spesifikasi' => 'XGA 1024x768, 3600 Lumens, HDMI/VGA',
                'kondisi' => 'baik',
                'status' => 'tersedia',
                'sumber_dana' => 'bos',
                'tahun_perolehan' => 2021,
                'harga_perolehan' => 5500000,
                'jumlah' => 1,
                'satuan' => 'unit',
            ],
            [
                'kode_inventaris' => 'INV-ELK-004',
                'nama' => 'Printer HP LaserJet',
                'sarpras_kategori_id' => $kategoriElk->id,
                'ruangan_id' => $ruangTu?->id,
                'tipe' => 'aset',
                'merk' => 'HP',
                'spesifikasi' => 'LaserJet Pro MFP M130fw, Print/Scan/Copy/Fax, WiFi',
                'kondisi' => 'baik',
                'status' => 'tersedia',
                'sumber_dana' => 'komite',
                'tahun_perolehan' => 2021,
                'harga_perolehan' => 3200000,
                'jumlah' => 1,
                'satuan' => 'unit',
            ],
            [
                'kode_inventaris' => 'INV-ELK-005',
                'nama' => 'Kipas Angin Panasonic',
                'sarpras_kategori_id' => $kategoriElk->id,
                'ruangan_id' => $ruangGuru?->id,
                'tipe' => 'aset',
                'merk' => 'Panasonic',
                'spesifikasi' => 'Standing fan 16 inch, 3 kecepatan',
                'kondisi' => 'rusak_ringan',
                'status' => 'perbaikan',
                'sumber_dana' => 'yayasan',
                'tahun_perolehan' => 2019,
                'harga_perolehan' => 650000,
                'jumlah' => 1,
                'satuan' => 'unit',
            ],
            [
                'kode_inventaris' => 'INV-ELK-006',
                'nama' => 'AC Split LG 1 PK',
                'sarpras_kategori_id' => $kategoriElk->id,
                'ruangan_id' => $ruangKepsek?->id,
                'tipe' => 'aset',
                'merk' => 'LG',
                'spesifikasi' => 'AC Split 1 PK, Inverter, mode cool/dry/fan',
                'kondisi' => 'baik',
                'status' => 'tersedia',
                'sumber_dana' => 'komite',
                'tahun_perolehan' => 2020,
                'harga_perolehan' => 4500000,
                'jumlah' => 1,
                'satuan' => 'unit',
            ],
            [
                'kode_inventaris' => 'INV-ELK-007',
                'nama' => 'Komputer PC Lab',
                'sarpras_kategori_id' => $kategoriElk->id,
                'ruangan_id' => $labKom?->id,
                'tipe' => 'aset',
                'merk' => 'Rakitan',
                'spesifikasi' => 'Intel Core i3, RAM 4GB, HDD 500GB, Monitor 19"',
                'kondisi' => 'baik',
                'status' => 'tersedia',
                'sumber_dana' => 'bos',
                'tahun_perolehan' => 2020,
                'harga_perolehan' => 5000000,
                'jumlah' => 1,
                'satuan' => 'unit',
            ],
            [
                'kode_inventaris' => 'INV-ELK-008',
                'nama' => 'Sound System Aula',
                'sarpras_kategori_id' => $kategoriElk->id,
                'ruangan_id' => $aula?->id,
                'tipe' => 'aset',
                'merk' => 'Yamaha',
                'spesifikasi' => 'Mixer 8 channel, 2x speaker 15 inch, 2x microphone wireless',
                'kondisi' => 'baik',
                'status' => 'tersedia',
                'sumber_dana' => 'yayasan',
                'tahun_perolehan' => 2021,
                'harga_perolehan' => 15000000,
                'jumlah' => 1,
                'satuan' => 'set',
            ],

            // --- Meubelair: Aset ---
            [
                'kode_inventaris' => 'INV-MBL-001',
                'nama' => 'Meja Guru Jati',
                'sarpras_kategori_id' => $kategoriMbl->id,
                'ruangan_id' => $ruangGuru?->id,
                'tipe' => 'aset',
                'merk' => null,
                'spesifikasi' => 'Meja kayu jati ukuran 120x60x75 cm, 1 laci',
                'kondisi' => 'baik',
                'status' => 'tersedia',
                'sumber_dana' => 'yayasan',
                'tahun_perolehan' => 2018,
                'harga_perolehan' => 1200000,
                'jumlah' => 1,
                'satuan' => 'unit',
            ],
            [
                'kode_inventaris' => 'INV-MBL-002',
                'nama' => 'Kursi Lipat Besi',
                'sarpras_kategori_id' => $kategoriMbl->id,
                'ruangan_id' => $aula?->id,
                'tipe' => 'aset',
                'merk' => null,
                'spesifikasi' => 'Kursi lipat rangka besi, dudukan busa biru',
                'kondisi' => 'baik',
                'status' => 'tersedia',
                'sumber_dana' => 'komite',
                'tahun_perolehan' => 2019,
                'harga_perolehan' => 250000,
                'jumlah' => 1,
                'satuan' => 'unit',
            ],
            [
                'kode_inventaris' => 'INV-MBL-003',
                'nama' => 'Lemari Arsip Besi',
                'sarpras_kategori_id' => $kategoriMbl->id,
                'ruangan_id' => $ruangTu?->id,
                'tipe' => 'aset',
                'merk' => null,
                'spesifikasi' => 'Lemari arsip besi 4 pintu, kunci, warna abu-abu',
                'kondisi' => 'baik',
                'status' => 'tersedia',
                'sumber_dana' => 'bos',
                'tahun_perolehan' => 2020,
                'harga_perolehan' => 1800000,
                'jumlah' => 1,
                'satuan' => 'unit',
            ],
            [
                'kode_inventaris' => 'INV-MBL-004',
                'nama' => 'Rak Buku Perpustakaan',
                'sarpras_kategori_id' => $kategoriMbl->id,
                'ruangan_id' => $perpus?->id,
                'tipe' => 'aset',
                'merk' => null,
                'spesifikasi' => 'Rak buku kayu 5 tingkat, lebar 90cm',
                'kondisi' => 'baik',
                'status' => 'tersedia',
                'sumber_dana' => 'bos',
                'tahun_perolehan' => 2019,
                'harga_perolehan' => 900000,
                'jumlah' => 1,
                'satuan' => 'unit',
            ],
            [
                'kode_inventaris' => 'INV-MBL-005',
                'nama' => 'Papan Tulis White Board',
                'sarpras_kategori_id' => $kategoriMbl->id,
                'ruangan_id' => null,
                'tipe' => 'aset',
                'merk' => null,
                'spesifikasi' => 'White board 120x240 cm dengan bingkai aluminium',
                'kondisi' => 'rusak_berat',
                'status' => 'dihapus',
                'sumber_dana' => 'yayasan',
                'tahun_perolehan' => 2015,
                'harga_perolehan' => 800000,
                'jumlah' => 1,
                'satuan' => 'unit',
            ],

            // --- Alat Lab: Aset ---
            [
                'kode_inventaris' => 'INV-LAB-001',
                'nama' => 'Mikroskop Binokuler',
                'sarpras_kategori_id' => $kategoriLab->id,
                'ruangan_id' => $labIpa?->id,
                'tipe' => 'aset',
                'merk' => 'Olympus',
                'spesifikasi' => 'Mikroskop binokuler perbesaran 40x-1000x, lensa akromatik',
                'kondisi' => 'baik',
                'status' => 'tersedia',
                'sumber_dana' => 'bos',
                'tahun_perolehan' => 2020,
                'harga_perolehan' => 3500000,
                'jumlah' => 1,
                'satuan' => 'unit',
            ],
            [
                'kode_inventaris' => 'INV-LAB-002',
                'nama' => 'Timbangan Digital Lab',
                'sarpras_kategori_id' => $kategoriLab->id,
                'ruangan_id' => $labIpa?->id,
                'tipe' => 'aset',
                'merk' => 'Ohaus',
                'spesifikasi' => 'Kapasitas 200g, ketelitian 0.001g',
                'kondisi' => 'baik',
                'status' => 'tersedia',
                'sumber_dana' => 'bos',
                'tahun_perolehan' => 2021,
                'harga_perolehan' => 2000000,
                'jumlah' => 1,
                'satuan' => 'unit',
            ],

            // --- Alat Olahraga: Aset & Bahan ---
            [
                'kode_inventaris' => 'INV-OLR-001',
                'nama' => 'Bola Sepak',
                'sarpras_kategori_id' => $kategoriOlr?->id ?? $kategoriLab->id,
                'ruangan_id' => $gudangOlr?->id,
                'tipe' => 'aset',
                'merk' => 'Mikasa',
                'spesifikasi' => 'Bola sepak ukuran 5, kulit sintetis',
                'kondisi' => 'baik',
                'status' => 'tersedia',
                'sumber_dana' => 'komite',
                'tahun_perolehan' => 2022,
                'harga_perolehan' => 350000,
                'jumlah' => 1,
                'satuan' => 'buah',
            ],
            [
                'kode_inventaris' => 'INV-OLR-002',
                'nama' => 'Net Bola Voli',
                'sarpras_kategori_id' => $kategoriOlr?->id ?? $kategoriLab->id,
                'ruangan_id' => $gudangOlr?->id,
                'tipe' => 'aset',
                'merk' => null,
                'spesifikasi' => 'Net voli standar 9.5m x 1m, benang nilon',
                'kondisi' => 'rusak_ringan',
                'status' => 'tersedia',
                'sumber_dana' => 'bos',
                'tahun_perolehan' => 2020,
                'harga_perolehan' => 400000,
                'jumlah' => 1,
                'satuan' => 'buah',
            ],

            // --- Kendaraan: Aset ---
            [
                'kode_inventaris' => 'INV-KDR-001',
                'nama' => 'Sepeda Motor Dinas',
                'sarpras_kategori_id' => $kategoriKdr?->id ?? $kategoriMbl->id,
                'ruangan_id' => null,
                'tipe' => 'aset',
                'merk' => 'Honda',
                'spesifikasi' => 'Honda Supra X 125, Tahun 2019, Warna Hitam, Nopol B 1234 XX',
                'kondisi' => 'baik',
                'status' => 'tersedia',
                'sumber_dana' => 'yayasan',
                'tahun_perolehan' => 2019,
                'harga_perolehan' => 18000000,
                'jumlah' => 1,
                'satuan' => 'unit',
            ],

            // --- Bahan Habis Pakai ---
            [
                'kode_inventaris' => 'INV-BHN-001',
                'nama' => 'Kertas HVS A4 80gr',
                'sarpras_kategori_id' => $kategoriMbl2->id,
                'ruangan_id' => $gudang?->id,
                'tipe' => 'bahan',
                'merk' => 'Sinar Dunia',
                'spesifikasi' => 'Kertas HVS A4 80gr, putih, 500 lembar/rim',
                'kondisi' => 'baik',
                'status' => 'tersedia',
                'sumber_dana' => 'bos',
                'tahun_perolehan' => 2024,
                'harga_perolehan' => 55000,
                'jumlah' => 50,
                'satuan' => 'rim',
            ],
            [
                'kode_inventaris' => 'INV-BHN-002',
                'nama' => 'Tinta Printer Hitam',
                'sarpras_kategori_id' => $kategoriElk->id,
                'ruangan_id' => $gudang?->id,
                'tipe' => 'bahan',
                'merk' => 'HP',
                'spesifikasi' => 'Tinta original HP 680 Black, 135 halaman',
                'kondisi' => 'baik',
                'status' => 'tersedia',
                'sumber_dana' => 'bos',
                'tahun_perolehan' => 2024,
                'harga_perolehan' => 85000,
                'jumlah' => 12,
                'satuan' => 'buah',
            ],
            [
                'kode_inventaris' => 'INV-BHN-003',
                'nama' => 'Spidol Board Marker',
                'sarpras_kategori_id' => $kategoriMbl2->id,
                'ruangan_id' => $gudang?->id,
                'tipe' => 'bahan',
                'merk' => 'Snowman',
                'spesifikasi' => 'Spidol whiteboard, warna hitam/merah/biru, 12 buah/box',
                'kondisi' => 'baik',
                'status' => 'tersedia',
                'sumber_dana' => 'bos',
                'tahun_perolehan' => 2024,
                'harga_perolehan' => 35000,
                'jumlah' => 20,
                'satuan' => 'box',
            ],
        ];

        $created = 0;
        foreach ($barangs as $data) {
            SarprasBarang::firstOrCreate(
                ['kode_inventaris' => $data['kode_inventaris']],
                array_merge(['foto' => null, 'keterangan' => null, 'is_active' => true], $data)
            );
            $created++;
        }

        $this->command->info("SarprasBarang seeded: {$created} barang.");
    }
}
