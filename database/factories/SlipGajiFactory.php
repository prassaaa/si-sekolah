<?php

namespace Database\Factories;

use App\Models\SlipGaji;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SlipGaji>
 */
class SlipGajiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tahun' => (int) date('Y'),
            'bulan' => (int) date('n'),
            'gaji_pokok' => '5000000.00',
            'total_tunjangan' => '1150000.00',
            'total_potongan' => '350000.00',
            'gaji_bersih' => '5800000.00',
            'detail_tunjangan' => [
                'jabatan' => '500000.00',
                'kehadiran' => '300000.00',
                'transport' => '200000.00',
                'makan' => '150000.00',
                'lainnya' => '0.00',
            ],
            'detail_potongan' => [
                'bpjs' => '250000.00',
                'pph21' => '100000.00',
                'lainnya' => '0.00',
            ],
            'status' => 'draft',
        ];
    }
}
