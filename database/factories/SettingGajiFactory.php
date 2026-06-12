<?php

namespace Database\Factories;

use App\Models\SettingGaji;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SettingGaji>
 */
class SettingGajiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gaji_pokok' => '5000000.00',
            'tunjangan_jabatan' => '500000.00',
            'tunjangan_kehadiran' => '300000.00',
            'tunjangan_transport' => '200000.00',
            'tunjangan_makan' => '150000.00',
            'tunjangan_lainnya' => '0.00',
            'potongan_bpjs' => '250000.00',
            'potongan_pph21' => '100000.00',
            'potongan_lainnya' => '0.00',
            'is_active' => true,
        ];
    }
}
