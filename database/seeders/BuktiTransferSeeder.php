<?php

namespace Database\Seeders;

use App\Models\BuktiTransfer;
use App\Models\Siswa;
use App\Models\TagihanSiswa;
use App\Models\User;
use Illuminate\Database\Seeder;

class BuktiTransferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $siswa = Siswa::first();
        $admin = User::first();

        if (! $siswa) {
            return;
        }

        $tagihan = TagihanSiswa::where('siswa_id', $siswa->id)->first();

        $buktiTransfers = [
            [
                'siswa_id' => $siswa->id,
                'tagihan_siswa_id' => $tagihan?->id,
                'nama_pengirim' => 'Budi Santoso',
                'bank_pengirim' => 'BCA',
                'nomor_rekening' => '1234567890',
                'nominal' => 500000,
                'tanggal_transfer' => now()->subDays(5),
                'bukti_file' => null,
                'status' => 'verified',
                'catatan_wali' => 'Pembayaran SPP bulan ini',
                'catatan_admin' => 'Sudah diverifikasi',
                'verified_by' => $admin?->id,
                'verified_at' => now()->subDays(4),
            ],
            [
                'siswa_id' => $siswa->id,
                'tagihan_siswa_id' => $tagihan?->id,
                'nama_pengirim' => 'Siti Nurhaliza',
                'bank_pengirim' => 'Mandiri',
                'nomor_rekening' => '0987654321',
                'nominal' => 750000,
                'tanggal_transfer' => now()->subDays(3),
                'bukti_file' => null,
                'status' => 'pending',
                'catatan_wali' => 'Pembayaran uang gedung',
                'catatan_admin' => null,
                'verified_by' => null,
                'verified_at' => null,
            ],
            [
                'siswa_id' => $siswa->id,
                'tagihan_siswa_id' => $tagihan?->id,
                'nama_pengirim' => 'Ahmad Hidayat',
                'bank_pengirim' => 'BSI',
                'nomor_rekening' => '5678901234',
                'nominal' => 1000000,
                'tanggal_transfer' => now()->subDays(1),
                'bukti_file' => null,
                'status' => 'rejected',
                'catatan_wali' => 'Pembayaran seragam',
                'catatan_admin' => 'Nominal tidak sesuai dengan tagihan',
                'verified_by' => $admin?->id,
                'verified_at' => now(),
            ],
        ];

        foreach ($buktiTransfers as $data) {
            BuktiTransfer::firstOrCreate(
                [
                    'siswa_id' => $data['siswa_id'],
                    'tanggal_transfer' => $data['tanggal_transfer'],
                    'nominal' => $data['nominal'],
                ],
                $data
            );
        }
    }
}
