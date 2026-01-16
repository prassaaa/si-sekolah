<?php

namespace Database\Factories;

use App\Models\Pegawai;
use App\Models\TagihanSiswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pembayaran>
 */
class PembayaranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tagihan_siswa_id' => TagihanSiswa::factory(),
            'nomor_transaksi' => 'PAY-'.fake()->unique()->numerify('######'),
            'tanggal_bayar' => fake()->dateTimeBetween('-1 month', 'now'),
            'jumlah_bayar' => fake()->randomElement([100000, 150000, 250000, 500000]),
            'metode_pembayaran' => fake()->randomElement(['tunai', 'transfer', 'qris']),
            'referensi_pembayaran' => fake()->optional(0.5)->numerify('REF-########'),
            'diterima_oleh' => Pegawai::factory(),
            'keterangan' => fake()->optional(0.3)->sentence(),
            'status' => 'berhasil',
        ];
    }

    public function tunai(): static
    {
        return $this->state(fn (array $attributes) => [
            'metode_pembayaran' => 'tunai',
            'referensi_pembayaran' => null,
        ]);
    }

    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'metode_pembayaran' => 'transfer',
            'referensi_pembayaran' => fake()->numerify('TRF-########'),
        ]);
    }
}
