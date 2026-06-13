<?php

namespace Database\Factories;

use App\Models\PeriodeAkuntansi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PeriodeAkuntansi>
 */
class PeriodeAkuntansiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tahun' => fake()->numberBetween(2025, 2027),
            'bulan' => fake()->numberBetween(1, 12),
            'status' => 'open',
            'closed_by' => null,
            'closed_at' => null,
            'keterangan' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
            'closed_by' => User::factory(),
            'closed_at' => now(),
        ]);
    }

    public function periode(int $tahun, int $bulan): static
    {
        return $this->state(fn (array $attributes) => [
            'tahun' => $tahun,
            'bulan' => $bulan,
        ]);
    }
}
