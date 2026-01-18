<?php

namespace Database\Seeders;

use App\Models\SettingGaji;
use App\Models\SlipGaji;
use App\Models\User;
use Carbon\Carbon;
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
        $today = Carbon::now();

        if ($settingGajis->isEmpty()) {
            $this->command->warn('Tidak ada setting gaji aktif. Silakan jalankan SettingGajiSeeder terlebih dahulu.');

            return;
        }

        $counter = 0;

        // Generate slip gaji untuk 6 bulan terakhir
        for ($monthOffset = 5; $monthOffset >= 0; $monthOffset--) {
            $bulan = $today->copy()->subMonths($monthOffset);
            $bulanNum = $bulan->month;
            $tahun = $bulan->year;

            foreach ($settingGajis as $setting) {
                // Skip jika slip gaji sudah ada
                $exists = SlipGaji::where('pegawai_id', $setting->pegawai_id)
                    ->where('tahun', $tahun)
                    ->where('bulan', $bulanNum)
                    ->exists();

                if ($exists) {
                    continue;
                }

                // Variasi kehadiran untuk realistis
                $kehadiranFactor = rand(90, 100) / 100; // 90-100% kehadiran
                $tunjanganKehadiran = $setting->tunjangan_kehadiran * $kehadiranFactor;

                $totalTunjangan = $setting->tunjangan_jabatan +
                    $tunjanganKehadiran +
                    $setting->tunjangan_transport +
                    $setting->tunjangan_makan +
                    $setting->tunjangan_lainnya;

                // Kadang ada potongan tambahan (keterlambatan, dll)
                $potonganTambahan = rand(1, 10) <= 2 ? rand(50, 200) * 1000 : 0;

                $totalPotongan = $setting->potongan_bpjs +
                    $setting->potongan_pph21 +
                    $setting->potongan_lainnya +
                    $potonganTambahan;

                $gajiBersih = $setting->gaji_pokok + $totalTunjangan - $totalPotongan;

                // Status: bulan lalu dan sebelumnya sudah dibayar, bulan ini bisa draft atau approved
                if ($monthOffset === 0) {
                    $statusRand = rand(1, 10);
                    if ($statusRand <= 5) {
                        $status = 'paid';
                    } elseif ($statusRand <= 8) {
                        $status = 'approved';
                    } else {
                        $status = 'draft';
                    }
                } else {
                    $status = 'paid';
                }

                $tanggalBayar = null;
                if ($status === 'paid') {
                    $tanggalBayar = $bulan->copy()->endOfMonth();
                    if ($tanggalBayar->isWeekend()) {
                        $tanggalBayar = $tanggalBayar->subDays(2);
                    }
                }

                $nomorSlip = 'SG-'.str_pad($tahun, 4, '0', STR_PAD_LEFT).'-'.str_pad($bulanNum, 2, '0', STR_PAD_LEFT).'-'.str_pad($setting->pegawai_id, 4, '0', STR_PAD_LEFT);

                $detailPotongan = [
                    'BPJS' => $setting->potongan_bpjs,
                    'PPh 21' => $setting->potongan_pph21,
                    'Potongan Lainnya' => $setting->potongan_lainnya,
                ];

                if ($potonganTambahan > 0) {
                    $detailPotongan['Potongan Keterlambatan'] = $potonganTambahan;
                }

                SlipGaji::create([
                    'nomor' => $nomorSlip,
                    'pegawai_id' => $setting->pegawai_id,
                    'setting_gaji_id' => $setting->id,
                    'tahun' => $tahun,
                    'bulan' => $bulanNum,
                    'gaji_pokok' => $setting->gaji_pokok,
                    'total_tunjangan' => $totalTunjangan,
                    'total_potongan' => $totalPotongan,
                    'gaji_bersih' => $gajiBersih,
                    'detail_tunjangan' => [
                        'Tunjangan Jabatan' => $setting->tunjangan_jabatan,
                        'Tunjangan Kehadiran' => $tunjanganKehadiran,
                        'Tunjangan Transport' => $setting->tunjangan_transport,
                        'Tunjangan Makan' => $setting->tunjangan_makan,
                        'Tunjangan Lainnya' => $setting->tunjangan_lainnya,
                    ],
                    'detail_potongan' => $detailPotongan,
                    'status' => $status,
                    'tanggal_bayar' => $tanggalBayar,
                    'catatan' => 'Gaji bulan '.$bulan->translatedFormat('F Y'),
                    'created_by' => $admin?->id,
                ]);

                $counter++;
            }
        }

        $this->command->info("SlipGaji seeded successfully: {$counter} records");
    }
}
