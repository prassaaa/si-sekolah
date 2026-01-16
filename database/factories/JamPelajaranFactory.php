<?php

namespace Database\Factories;

use App\Models\JamPelajaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JamPelajaran>
 */
class JamPelajaranFactory extends Factory
{
    protected $model = JamPelajaran::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jamKe = fake()->numberBetween(1, 10);
        $waktuMulai = fake()->time('H:i:s');
        $durasi = 45;

        return [
            'jam_ke' => $jamKe,
            'waktu_mulai' => $waktuMulai,
            'waktu_selesai' => date('H:i:s', strtotime($waktuMulai) + ($durasi * 60)),
            'durasi' => $durasi,
            'jenis' => 'Reguler',
            'keterangan' => null,
            'is_active' => true,
        ];
    }

    public function istirahat(): static
    {
        return $this->state(fn (array $attributes): array => [
            'jenis' => 'Istirahat',
            'durasi' => 15,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
