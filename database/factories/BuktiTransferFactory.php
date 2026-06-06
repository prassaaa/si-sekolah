<?php

namespace Database\Factories;

use App\Models\Siswa;
use App\Models\TagihanSiswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BuktiTransfer>
 */
class BuktiTransferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'siswa_id' => Siswa::factory(),
            'tagihan_siswa_id' => TagihanSiswa::factory(),
            'nama_pengirim' => $this->faker->name(),
            'bank_pengirim' => $this->faker->randomElement(['BCA', 'BRI', 'BNI', 'Mandiri']),
            'nomor_rekening' => $this->faker->numerify('##########'),
            'nominal' => $this->faker->numberBetween(50000, 1000000),
            'tanggal_transfer' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'status' => 'pending',
            'catatan_wali' => null,
        ];
    }
}
