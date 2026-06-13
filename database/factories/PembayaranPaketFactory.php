<?php

namespace Database\Factories;

use App\Models\PembayaranPaket;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PembayaranPaket>
 */
class PembayaranPaketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'Paket '.fake()->unique()->words(2, true),
            'tahun_ajaran_id' => TahunAjaran::factory(),
            'total_biaya' => fake()->randomElement([500000, 1000000, 1500000]),
            'deskripsi' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
