<?php

namespace Database\Seeders;

use App\Models\SarprasKategori;
use App\Models\SarprasPengadaan;
use App\Models\SarprasPengadaanItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SarprasPengadaanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            $this->command->warn('Tidak ada user. Jalankan UserSeeder terlebih dahulu.');

            return;
        }

        $kategoriElk = SarprasKategori::where('kode', 'ELK')->first();
        $kategoriMbl = SarprasKategori::where('kode', 'MBL')->first();
        $kategoriOlr = SarprasKategori::where('kode', 'OLR')->first();
        $kategoriBkm = SarprasKategori::where('kode', 'BKM')->first();

        if (! $kategoriElk) {
            $this->command->warn('Kategori belum ada. Jalankan SarprasKategoriSeeder terlebih dahulu.');

            return;
        }

        $pengadaans = [
            // Pengadaan sudah diterima
            [
                'header' => [
                    'tanggal' => Carbon::now()->subDays(90)->toDateString(),
                    'sumber_dana' => 'bos',
                    'penyedia' => 'CV Komputer Nusantara',
                    'total_biaya' => 0,
                    'status' => 'diterima',
                    'keterangan' => 'Pengadaan perangkat komputer lab semester ganjil.',
                    'dibuat_oleh' => $user->id,
                ],
                'items' => [
                    [
                        'nama_barang' => 'Laptop Siswa',
                        'sarpras_kategori_id' => $kategoriElk->id,
                        'jumlah' => 5,
                        'satuan' => 'unit',
                        'harga_satuan' => 7500000,
                    ],
                    [
                        'nama_barang' => 'Mouse Wireless',
                        'sarpras_kategori_id' => $kategoriElk->id,
                        'jumlah' => 10,
                        'satuan' => 'buah',
                        'harga_satuan' => 120000,
                    ],
                ],
            ],
            // Pengadaan disetujui, belum diterima
            [
                'header' => [
                    'tanggal' => Carbon::now()->subDays(30)->toDateString(),
                    'sumber_dana' => 'komite',
                    'penyedia' => 'Toko Furniture Sejahtera',
                    'total_biaya' => 0,
                    'status' => 'disetujui',
                    'keterangan' => 'Pengadaan meubelair ruang guru.',
                    'dibuat_oleh' => $user->id,
                ],
                'items' => [
                    [
                        'nama_barang' => 'Meja Kerja Guru',
                        'sarpras_kategori_id' => $kategoriMbl?->id ?? $kategoriElk->id,
                        'jumlah' => 5,
                        'satuan' => 'unit',
                        'harga_satuan' => 1500000,
                    ],
                    [
                        'nama_barang' => 'Kursi Ergonomis',
                        'sarpras_kategori_id' => $kategoriMbl?->id ?? $kategoriElk->id,
                        'jumlah' => 5,
                        'satuan' => 'unit',
                        'harga_satuan' => 800000,
                    ],
                    [
                        'nama_barang' => 'Lemari Kecil',
                        'sarpras_kategori_id' => $kategoriMbl?->id ?? $kategoriElk->id,
                        'jumlah' => 2,
                        'satuan' => 'unit',
                        'harga_satuan' => 650000,
                    ],
                ],
            ],
            // Pengadaan masih draft
            [
                'header' => [
                    'tanggal' => Carbon::now()->subDays(5)->toDateString(),
                    'sumber_dana' => 'bos',
                    'penyedia' => null,
                    'total_biaya' => 0,
                    'status' => 'draft',
                    'keterangan' => 'Rencana pengadaan alat olahraga semester genap.',
                    'dibuat_oleh' => $user->id,
                ],
                'items' => [
                    [
                        'nama_barang' => 'Bola Basket',
                        'sarpras_kategori_id' => $kategoriOlr?->id ?? $kategoriElk->id,
                        'jumlah' => 4,
                        'satuan' => 'buah',
                        'harga_satuan' => 450000,
                    ],
                    [
                        'nama_barang' => 'Matras Senam',
                        'sarpras_kategori_id' => $kategoriOlr?->id ?? $kategoriElk->id,
                        'jumlah' => 6,
                        'satuan' => 'lembar',
                        'harga_satuan' => 350000,
                    ],
                ],
            ],
            // Pengadaan dibatalkan
            [
                'header' => [
                    'tanggal' => Carbon::now()->subDays(60)->toDateString(),
                    'sumber_dana' => 'yayasan',
                    'penyedia' => 'Toko Buku Cerdas',
                    'total_biaya' => 0,
                    'status' => 'batal',
                    'keterangan' => 'Dibatalkan karena anggaran dialihkan ke kegiatan lain.',
                    'dibuat_oleh' => $user->id,
                ],
                'items' => [
                    [
                        'nama_barang' => 'Buku Referensi IPA',
                        'sarpras_kategori_id' => $kategoriBkm?->id ?? $kategoriElk->id,
                        'jumlah' => 20,
                        'satuan' => 'buah',
                        'harga_satuan' => 85000,
                    ],
                ],
            ],
        ];

        $createdHeader = 0;
        $createdItem = 0;

        foreach ($pengadaans as $entry) {
            // nomor di-generate otomatis oleh booted() — jangan hardcode
            $pengadaan = SarprasPengadaan::firstOrCreate(
                [
                    'tanggal' => $entry['header']['tanggal'],
                    'status' => $entry['header']['status'],
                    'dibuat_oleh' => $entry['header']['dibuat_oleh'],
                    'keterangan' => $entry['header']['keterangan'],
                ],
                $entry['header']
            );
            $createdHeader++;

            $totalBiaya = '0';

            foreach ($entry['items'] as $itemData) {
                $subtotal = bcmul((string) $itemData['jumlah'], (string) $itemData['harga_satuan'], 2);

                SarprasPengadaanItem::firstOrCreate(
                    [
                        'sarpras_pengadaan_id' => $pengadaan->id,
                        'nama_barang' => $itemData['nama_barang'],
                    ],
                    array_merge($itemData, [
                        'sarpras_pengadaan_id' => $pengadaan->id,
                        'subtotal' => $subtotal,
                    ])
                );

                $totalBiaya = bcadd($totalBiaya, $subtotal, 2);
                $createdItem++;
            }

            // Update total_biaya
            $pengadaan->update(['total_biaya' => $totalBiaya]);
        }

        $this->command->info("SarprasPengadaan seeded: {$createdHeader} pengadaan, {$createdItem} item.");
    }
}
