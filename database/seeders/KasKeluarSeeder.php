<?php

namespace Database\Seeders;

use App\Models\Akun;
use App\Models\KasKeluar;
use App\Models\User;
use Illuminate\Database\Seeder;

class KasKeluarSeeder extends Seeder
{
    public function run(): void
    {
        $akunKas = Akun::where('kode', '1-1001')->first();
        $admin = User::first();

        if (! $akunKas) {
            $this->command->warn('Akun kas tidak ditemukan.');

            return;
        }

        $kasKeluars = [
            [
                'nomor_bukti' => 'KK-'.now()->format('Ymd').'-001',
                'akun_id' => $akunKas->id,
                'tanggal' => now()->subDays(8),
                'nominal' => 500000,
                'penerima' => 'Toko ATK Sejahtera',
                'keterangan' => 'Pembelian ATK untuk kantor',
                'user_id' => $admin?->id,
            ],
            [
                'nomor_bukti' => 'KK-'.now()->format('Ymd').'-002',
                'akun_id' => $akunKas->id,
                'tanggal' => now()->subDays(6),
                'nominal' => 1500000,
                'penerima' => 'PLN',
                'keterangan' => 'Pembayaran listrik bulan ini',
                'user_id' => $admin?->id,
            ],
            [
                'nomor_bukti' => 'KK-'.now()->format('Ymd').'-003',
                'akun_id' => $akunKas->id,
                'tanggal' => now()->subDays(3),
                'nominal' => 300000,
                'penerima' => 'PDAM',
                'keterangan' => 'Pembayaran air bulan ini',
                'user_id' => $admin?->id,
            ],
        ];

        foreach ($kasKeluars as $kas) {
            KasKeluar::firstOrCreate(['nomor_bukti' => $kas['nomor_bukti']], $kas);
        }

        $this->command->info('Kas Keluar seeded successfully: '.count($kasKeluars).' records');
    }
}
