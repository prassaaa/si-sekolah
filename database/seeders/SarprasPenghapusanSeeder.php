<?php

namespace Database\Seeders;

use App\Models\SarprasBarang;
use App\Models\SarprasPenghapusan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SarprasPenghapusanSeeder extends Seeder
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

        // Pilih barang dengan kondisi rusak atau sudah dihapus statusnya
        $barangRusak = SarprasBarang::where('kondisi', 'rusak_berat')->first();
        $barangDihapus = SarprasBarang::where('status', 'dihapus')->first();
        $barangLain = SarprasBarang::where('kondisi', 'rusak_ringan')->first();
        $barangUsang = SarprasBarang::where('tipe', 'aset')
            ->where('tahun_perolehan', '<=', 2018)
            ->first();

        $records = [];

        // Penghapusan disetujui (barang sudah berstatus dihapus)
        if ($barangDihapus) {
            $records[] = [
                'sarpras_barang_id' => $barangDihapus->id,
                'tanggal' => Carbon::now()->subDays(30)->toDateString(),
                'alasan' => 'rusak_berat',
                'jumlah' => 1,
                'nilai_sisa' => 0,
                'metode' => 'dibuang',
                'disetujui_oleh' => $user->id,
                'status' => 'disetujui',
                'keterangan' => 'Papan tulis rusak berat, tidak layak pakai lagi.',
            ];
        }

        // Penghapusan diajukan - barang rusak berat
        if ($barangRusak) {
            $records[] = [
                'sarpras_barang_id' => $barangRusak->id,
                'tanggal' => Carbon::now()->subDays(10)->toDateString(),
                'alasan' => 'rusak_berat',
                'jumlah' => 1,
                'nilai_sisa' => 100000,
                'metode' => 'dijual',
                'disetujui_oleh' => null,
                'status' => 'diajukan',
                'keterangan' => 'Barang sudah tidak dapat diperbaiki, diajukan untuk dijual sebagai besi tua.',
            ];
        }

        // Penghapusan ditolak
        if ($barangLain) {
            $records[] = [
                'sarpras_barang_id' => $barangLain->id,
                'tanggal' => Carbon::now()->subDays(20)->toDateString(),
                'alasan' => 'lainnya',
                'jumlah' => 1,
                'nilai_sisa' => 0,
                'metode' => 'disumbangkan',
                'disetujui_oleh' => $user->id,
                'status' => 'ditolak',
                'keterangan' => 'Ditolak karena kondisi masih bisa diperbaiki.',
            ];
        }

        // Penghapusan diajukan - barang usang
        if ($barangUsang && $barangUsang->id !== ($barangRusak?->id) && $barangUsang->id !== ($barangDihapus?->id)) {
            $records[] = [
                'sarpras_barang_id' => $barangUsang->id,
                'tanggal' => Carbon::now()->subDays(5)->toDateString(),
                'alasan' => 'usang',
                'jumlah' => 1,
                'nilai_sisa' => 500000,
                'metode' => 'disumbangkan',
                'disetujui_oleh' => null,
                'status' => 'diajukan',
                'keterangan' => 'Aset sudah berumur lebih dari 8 tahun dan sudah usang.',
            ];
        }

        if (empty($records)) {
            $this->command->warn('Tidak ada barang yang sesuai kriteria penghapusan. Pastikan SarprasBarangSeeder sudah dijalankan.');

            return;
        }

        $created = 0;
        foreach ($records as $data) {
            // nomor di-generate otomatis oleh booted() — jangan hardcode
            SarprasPenghapusan::firstOrCreate(
                [
                    'sarpras_barang_id' => $data['sarpras_barang_id'],
                    'tanggal' => $data['tanggal'],
                    'status' => $data['status'],
                ],
                $data
            );
            $created++;
        }

        $this->command->info("SarprasPenghapusan seeded: {$created} record.");
    }
}
