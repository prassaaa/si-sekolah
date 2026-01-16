<?php

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\Pegawai;
use App\Models\Semester;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KenaikanKelas>
 */
class KenaikanKelasFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['naik', 'tinggal', 'pending']);

        return [
            'siswa_id' => Siswa::factory(),
            'semester_id' => Semester::factory(),
            'kelas_asal_id' => Kelas::factory(),
            'kelas_tujuan_id' => $status === 'naik' ? Kelas::factory() : null,
            'status' => $status,
            'nilai_rata_rata' => fake()->randomFloat(2, 60, 100),
            'peringkat' => fake()->numberBetween(1, 40),
            'catatan' => fake()->optional(0.3)->sentence(),
            'tanggal_keputusan' => $status !== 'pending' ? fake()->date() : null,
            'disetujui_oleh' => $status !== 'pending' ? Pegawai::factory() : null,
        ];
    }

    public function naik(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'naik',
            'kelas_tujuan_id' => Kelas::factory(),
            'tanggal_keputusan' => fake()->date(),
        ]);
    }

    public function tinggal(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'tinggal',
            'kelas_tujuan_id' => null,
            'tanggal_keputusan' => fake()->date(),
        ]);
    }
}
