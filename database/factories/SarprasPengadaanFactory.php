<?php

namespace Database\Factories;

use App\Models\SarprasPengadaan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SarprasPengadaan>
 */
class SarprasPengadaanFactory extends Factory
{
    protected $model = SarprasPengadaan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tanggal' => fake()->dateTimeBetween('-3 months', 'now'),
            'sumber_dana' => fake()->randomElement(['bos', 'komite', 'yayasan', 'hibah', 'pribadi', 'lainnya']),
            'penyedia' => fake()->optional()->company(),
            'total_biaya' => 0,
            'status' => 'draft',
            'keterangan' => fake()->optional()->sentence(),
            'dibuat_oleh' => User::factory(),
        ];
    }

    public function disetujui(): static
    {
        return $this->state(fn () => ['status' => 'disetujui']);
    }

    public function diterima(): static
    {
        return $this->state(fn () => ['status' => 'diterima']);
    }
}
