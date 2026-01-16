<?php

namespace Database\Factories;

use App\Models\KategoriPembayaran;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JenisPembayaran>
 */
class JenisPembayaranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jenis = fake()->randomElement(['bulanan', 'tahunan', 'sekali_bayar', 'insidental']);

        return [
            'kategori_pembayaran_id' => KategoriPembayaran::factory(),
            'tahun_ajaran_id' => TahunAjaran::factory(),
            'kode' => strtoupper(fake()->unique()->lexify('JP-???')),
            'nama' => fake()->randomElement(['SPP Bulanan', 'Uang Gedung', 'Seragam Lengkap', 'Buku Paket', 'Study Tour']),
            'nominal' => fake()->randomElement([150000, 250000, 500000, 750000, 1000000, 1500000]),
            'jenis' => $jenis,
            'deskripsi' => fake()->optional()->sentence(),
            'is_active' => true,
            'tanggal_jatuh_tempo' => $jenis === 'bulanan' ? null : fake()->dateTimeBetween('now', '+6 months'),
        ];
    }

    public function bulanan(): static
    {
        return $this->state(fn (array $attributes) => [
            'jenis' => 'bulanan',
            'nominal' => 250000,
        ]);
    }
}
