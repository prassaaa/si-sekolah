<?php

namespace Database\Seeders;

use App\Models\JenisPembayaran;
use App\Models\PembayaranPaket;
use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class PembayaranPaketSeeder extends Seeder
{
    public function run(): void
    {
        $tahunAjaran = TahunAjaran::where('is_active', true)->first();

        if (! $tahunAjaran) {
            $this->command->warn('Tidak ada tahun ajaran aktif.');

            return;
        }

        $jenisPembayarans = JenisPembayaran::where('is_active', true)->pluck('id')->toArray();

        if (empty($jenisPembayarans)) {
            $this->command->warn('Tidak ada jenis pembayaran.');

            return;
        }

        $pakets = [
            [
                'nama' => 'Paket Siswa Baru Kelas 7',
                'tahun_ajaran_id' => $tahunAjaran->id,
                'total_biaya' => 5000000,
                'keterangan' => 'Paket biaya untuk siswa baru kelas 7',
                'is_active' => true,
            ],
            [
                'nama' => 'Paket Siswa Baru Kelas 10',
                'tahun_ajaran_id' => $tahunAjaran->id,
                'total_biaya' => 6000000,
                'keterangan' => 'Paket biaya untuk siswa baru kelas 10',
                'is_active' => true,
            ],
            [
                'nama' => 'Paket Tahunan',
                'tahun_ajaran_id' => $tahunAjaran->id,
                'total_biaya' => 3500000,
                'keterangan' => 'Paket biaya tahunan reguler',
                'is_active' => true,
            ],
        ];

        foreach ($pakets as $paket) {
            PembayaranPaket::firstOrCreate(
                ['nama' => $paket['nama'], 'tahun_ajaran_id' => $paket['tahun_ajaran_id']],
                $paket
            );
        }

        $this->command->info('Pembayaran Paket seeded successfully');
    }
}
