<?php

namespace Database\Seeders;

use App\Models\SettingGaji;
use App\Models\SlipGaji;
use App\Models\User;
use Illuminate\Database\Seeder;

class SlipGajiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settingGajis = SettingGaji::with('pegawai')->where('is_active', true)->get();
        $admin = User::first();
        $bulanSekarang = now()->month;
        $tahunSekarang = now()->year;

        $nomorCounter = 1;

        foreach ($settingGajis as $setting) {
            // Bulan lalu
            $bulanLalu = $bulanSekarang - 1;
            $tahunBulanLalu = $tahunSekarang;
            if ($bulanLalu < 1) {
                $bulanLalu = 12;
                $tahunBulanLalu = $tahunSekarang - 1;
            }

            $totalTunjangan = $setting->tunjangan_jabatan + $setting->tunjangan_kehadiran +
                $setting->tunjangan_transport + $setting->tunjangan_makan + $setting->tunjangan_lainnya;
            $totalPotongan = $setting->potongan_bpjs + $setting->potongan_pph21 + $setting->potongan_lainnya;
            $gajiBersih = $setting->gaji_pokok + $totalTunjangan - $totalPotongan;

            SlipGaji::firstOrCreate(
                [
                    'pegawai_id' => $setting->pegawai_id,
                    'tahun' => $tahunBulanLalu,
                    'bulan' => $bulanLalu,
                ],
                [
                    'nomor' => 'SG-'.str_pad($tahunBulanLalu, 4, '0', STR_PAD_LEFT).'-'.str_pad($bulanLalu, 2, '0', STR_PAD_LEFT).'-'.str_pad($nomorCounter++, 4, '0', STR_PAD_LEFT),
                    'setting_gaji_id' => $setting->id,
                    'gaji_pokok' => $setting->gaji_pokok,
                    'total_tunjangan' => $totalTunjangan,
                    'total_potongan' => $totalPotongan,
                    'gaji_bersih' => $gajiBersih,
                    'detail_tunjangan' => [
                        'Tunjangan Jabatan' => $setting->tunjangan_jabatan,
                        'Tunjangan Kehadiran' => $setting->tunjangan_kehadiran,
                        'Tunjangan Transport' => $setting->tunjangan_transport,
                        'Tunjangan Makan' => $setting->tunjangan_makan,
                        'Tunjangan Lainnya' => $setting->tunjangan_lainnya,
                    ],
                    'detail_potongan' => [
                        'BPJS' => $setting->potongan_bpjs,
                        'PPh 21' => $setting->potongan_pph21,
                        'Potongan Lainnya' => $setting->potongan_lainnya,
                    ],
                    'status' => 'paid',
                    'tanggal_bayar' => now()->subMonth()->endOfMonth(),
                    'catatan' => 'Gaji bulan '.\Carbon\Carbon::create($tahunBulanLalu, $bulanLalu)->translatedFormat('F Y'),
                    'created_by' => $admin?->id,
                ]
            );
        }
    }
}
