<?php

namespace Database\Seeders;

use App\Models\Pegawai;
use App\Models\SettingGaji;
use Illuminate\Database\Seeder;

class SettingGajiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pegawais = Pegawai::all();

        $settingGajiData = [
            // Kepala Sekolah
            [
                'gaji_pokok' => 8000000,
                'tunjangan_jabatan' => 3000000,
                'tunjangan_kehadiran' => 500000,
                'tunjangan_transport' => 750000,
                'tunjangan_makan' => 500000,
                'tunjangan_lainnya' => 0,
                'potongan_bpjs' => 400000,
                'potongan_pph21' => 250000,
                'potongan_lainnya' => 0,
            ],
            // Guru Senior
            [
                'gaji_pokok' => 5500000,
                'tunjangan_jabatan' => 1000000,
                'tunjangan_kehadiran' => 500000,
                'tunjangan_transport' => 500000,
                'tunjangan_makan' => 400000,
                'tunjangan_lainnya' => 0,
                'potongan_bpjs' => 275000,
                'potongan_pph21' => 100000,
                'potongan_lainnya' => 0,
            ],
            // Guru Junior
            [
                'gaji_pokok' => 4000000,
                'tunjangan_jabatan' => 500000,
                'tunjangan_kehadiran' => 400000,
                'tunjangan_transport' => 400000,
                'tunjangan_makan' => 350000,
                'tunjangan_lainnya' => 0,
                'potongan_bpjs' => 200000,
                'potongan_pph21' => 50000,
                'potongan_lainnya' => 0,
            ],
            // Staff TU
            [
                'gaji_pokok' => 3500000,
                'tunjangan_jabatan' => 300000,
                'tunjangan_kehadiran' => 400000,
                'tunjangan_transport' => 350000,
                'tunjangan_makan' => 300000,
                'tunjangan_lainnya' => 0,
                'potongan_bpjs' => 175000,
                'potongan_pph21' => 0,
                'potongan_lainnya' => 0,
            ],
        ];

        foreach ($pegawais as $index => $pegawai) {
            $dataIndex = min($index, count($settingGajiData) - 1);

            SettingGaji::firstOrCreate(
                ['pegawai_id' => $pegawai->id],
                array_merge($settingGajiData[$dataIndex], [
                    'is_active' => true,
                ])
            );
        }
    }
}
