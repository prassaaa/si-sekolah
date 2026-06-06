<?php

namespace Database\Seeders;

use App\Models\SarprasBarang;
use App\Models\SarprasPemeliharaan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SarprasPemeliharaanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $barangs = SarprasBarang::whereIn('tipe', ['aset'])->take(6)->get();

        if ($barangs->isEmpty()) {
            $this->command->warn('Tidak ada barang aset. Jalankan SarprasBarangSeeder terlebih dahulu.');

            return;
        }

        $user = User::first();

        if (! $user) {
            $this->command->warn('Tidak ada user. Jalankan UserSeeder terlebih dahulu.');

            return;
        }

        $records = [];
        $created = 0;

        // Pemeliharaan selesai - rutin
        if ($barangs->get(0)) {
            $tgl = Carbon::now()->subDays(60);
            $records[] = [
                'sarpras_barang_id' => $barangs->get(0)->id,
                'jenis' => 'rutin',
                'tanggal' => $tgl->toDateString(),
                'tanggal_selesai' => $tgl->copy()->addDays(1)->toDateString(),
                'deskripsi_masalah' => 'Pemeliharaan rutin bulanan: pembersihan dan pengecekan komponen.',
                'tindakan' => 'Pembersihan debu, pengecekan baut, pelumasan bagian bergerak.',
                'pelaksana' => 'internal',
                'nama_vendor' => null,
                'biaya' => 0,
                'kondisi_sebelum' => 'baik',
                'kondisi_sesudah' => 'baik',
                'status' => 'selesai',
                'dicatat_oleh' => $user->id,
            ];
        }

        // Pemeliharaan selesai - perbaikan oleh vendor
        if ($barangs->get(1)) {
            $tgl = Carbon::now()->subDays(45);
            $records[] = [
                'sarpras_barang_id' => $barangs->get(1)->id,
                'jenis' => 'perbaikan',
                'tanggal' => $tgl->toDateString(),
                'tanggal_selesai' => $tgl->copy()->addDays(5)->toDateString(),
                'deskripsi_masalah' => 'Kerusakan pada komponen internal, tidak bisa menyala.',
                'tindakan' => 'Penggantian power supply dan pembersihan motherboard.',
                'pelaksana' => 'vendor',
                'nama_vendor' => 'CV Elektronik Jaya',
                'biaya' => 450000,
                'kondisi_sebelum' => 'rusak_ringan',
                'kondisi_sesudah' => 'baik',
                'status' => 'selesai',
                'dicatat_oleh' => $user->id,
            ];
        }

        // Pemeliharaan sedang proses
        if ($barangs->get(2)) {
            $tgl = Carbon::now()->subDays(3);
            $records[] = [
                'sarpras_barang_id' => $barangs->get(2)->id,
                'jenis' => 'perbaikan',
                'tanggal' => $tgl->toDateString(),
                'tanggal_selesai' => null,
                'deskripsi_masalah' => 'Suara berisik saat beroperasi, perlu pengecekan.',
                'tindakan' => null,
                'pelaksana' => 'vendor',
                'nama_vendor' => 'Toko Teknik Mandiri',
                'biaya' => 200000,
                'kondisi_sebelum' => 'rusak_ringan',
                'kondisi_sesudah' => null,
                'status' => 'proses',
                'dicatat_oleh' => $user->id,
            ];
        }

        // Pemeliharaan dijadwalkan
        if ($barangs->get(3)) {
            $tgl = Carbon::now()->addDays(7);
            $records[] = [
                'sarpras_barang_id' => $barangs->get(3)->id,
                'jenis' => 'kalibrasi',
                'tanggal' => $tgl->toDateString(),
                'tanggal_selesai' => null,
                'deskripsi_masalah' => 'Kalibrasi berkala sesuai jadwal perawatan tahunan.',
                'tindakan' => null,
                'pelaksana' => 'vendor',
                'nama_vendor' => 'PT Alat Ukur Presisi',
                'biaya' => 750000,
                'kondisi_sebelum' => 'baik',
                'kondisi_sesudah' => null,
                'status' => 'dijadwalkan',
                'dicatat_oleh' => $user->id,
            ];
        }

        // Pemeliharaan dibatalkan
        if ($barangs->get(4)) {
            $tgl = Carbon::now()->subDays(15);
            $records[] = [
                'sarpras_barang_id' => $barangs->get(4)->id,
                'jenis' => 'rutin',
                'tanggal' => $tgl->toDateString(),
                'tanggal_selesai' => null,
                'deskripsi_masalah' => 'Pemeliharaan rutin triwulan.',
                'tindakan' => null,
                'pelaksana' => 'internal',
                'nama_vendor' => null,
                'biaya' => 0,
                'kondisi_sebelum' => 'baik',
                'kondisi_sesudah' => null,
                'status' => 'batal',
                'dicatat_oleh' => $user->id,
            ];
        }

        foreach ($records as $data) {
            SarprasPemeliharaan::firstOrCreate(
                [
                    'sarpras_barang_id' => $data['sarpras_barang_id'],
                    'tanggal' => $data['tanggal'],
                    'jenis' => $data['jenis'],
                ],
                $data
            );
            $created++;
        }

        $this->command->info("SarprasPemeliharaan seeded: {$created} record.");
    }
}
