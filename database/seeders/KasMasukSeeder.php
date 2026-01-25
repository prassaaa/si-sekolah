<?php

namespace Database\Seeders;

use App\Models\Akun;
use App\Models\KasMasuk;
use App\Models\User;
use Illuminate\Database\Seeder;

class KasMasukSeeder extends Seeder
{
    public function run(): void
    {
        $akunKas = Akun::where('kode', '1-1001')->first();
        $akunBank = Akun::where('kode', '1-1002')->first();
        $admin = User::first();

        if (! $akunKas) {
            $this->command->warn('Akun kas tidak ditemukan.');

            return;
        }

        $kasMasuks = [
            [
                'nomor_bukti' => 'KM-'.now()->format('Ymd').'-001',
                'akun_id' => $akunKas->id,
                'tanggal' => now()->subDays(10),
                'nominal' => 5000000,
                'sumber' => 'Pembayaran SPP',
                'keterangan' => 'Pembayaran SPP siswa',
                'user_id' => $admin?->id,
            ],
            [
                'nomor_bukti' => 'KM-'.now()->format('Ymd').'-002',
                'akun_id' => $akunBank?->id ?? $akunKas->id,
                'tanggal' => now()->subDays(5),
                'nominal' => 10000000,
                'sumber' => 'Transfer dari Yayasan',
                'keterangan' => 'Dana operasional dari yayasan',
                'user_id' => $admin?->id,
            ],
            [
                'nomor_bukti' => 'KM-'.now()->format('Ymd').'-003',
                'akun_id' => $akunKas->id,
                'tanggal' => now()->subDays(2),
                'nominal' => 2500000,
                'sumber' => 'Uang Kegiatan',
                'keterangan' => 'Iuran kegiatan study tour',
                'user_id' => $admin?->id,
            ],
        ];

        foreach ($kasMasuks as $kas) {
            KasMasuk::firstOrCreate(['nomor_bukti' => $kas['nomor_bukti']], $kas);
        }

        $this->command->info('Kas Masuk seeded successfully: '.count($kasMasuks).' records');
    }
}
