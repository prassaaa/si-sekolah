<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KategoriPembayaran>
 */
class KategoriPembayaranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => strtoupper(fake()->unique()->lexify('KAT-???')),
            'nama' => fake()->randomElement(['SPP', 'Uang Gedung', 'Seragam', 'Buku', 'Kegiatan', 'Ekstrakurikuler']),
            'deskripsi' => fake()->optional()->sentence(),
            'is_active' => true,
            'urutan' => fake()->numberBetween(1, 10),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
