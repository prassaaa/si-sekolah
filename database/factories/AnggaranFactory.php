<?php

namespace Database\Factories;

use App\Models\Akun;
use App\Models\Anggaran;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Anggaran>
 */
class AnggaranFactory extends Factory
{
    protected $model = Anggaran::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tahun_ajaran_id' => TahunAjaran::factory(),
            'akun_id' => Akun::factory(),
            'nominal_anggaran' => fake()->numberBetween(1_000_000, 100_000_000),
            'keterangan' => fake()->optional()->sentence(),
        ];
    }

    public function pendapatan(): static
    {
        return $this->state(fn (array $attributes) => [
            'akun_id' => Akun::factory()->pendapatan(),
        ]);
    }

    public function beban(): static
    {
        return $this->state(fn (array $attributes) => [
            'akun_id' => Akun::factory()->beban(),
        ]);
    }
}
