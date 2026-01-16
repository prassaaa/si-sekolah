<?php

namespace Database\Factories;

use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TahunAjaran>
 */
class TahunAjaranFactory extends Factory
{
    protected $model = TahunAjaran::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->numberBetween(2020, 2025);
        $nextYear = $year + 1;

        return [
            'kode' => $year.'/'.$nextYear,
            'nama' => 'Tahun Ajaran '.$year.'/'.$nextYear,
            'tanggal_mulai' => $year.'-07-15',
            'tanggal_selesai' => $nextYear.'-06-30',
            'is_active' => false,
            'keterangan' => fake()->optional()->sentence(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    public function current(): static
    {
        return $this->state(fn (array $attributes): array => [
            'kode' => '2025/2026',
            'nama' => 'Tahun Ajaran 2025/2026',
            'tanggal_mulai' => '2025-07-15',
            'tanggal_selesai' => '2026-06-30',
            'is_active' => true,
        ]);
    }
}
