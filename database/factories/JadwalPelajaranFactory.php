<?php

namespace Database\Factories;

use App\Models\JadwalPelajaran;
use App\Models\JamPelajaran;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pegawai;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JadwalPelajaran>
 */
class JadwalPelajaranFactory extends Factory
{
    protected $model = JadwalPelajaran::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'semester_id' => Semester::factory(),
            'kelas_id' => Kelas::factory(),
            'mata_pelajaran_id' => MataPelajaran::factory(),
            'jam_pelajaran_id' => JamPelajaran::factory(),
            'guru_id' => null,
            'hari' => fake()->randomElement(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']),
            'keterangan' => null,
            'is_active' => true,
        ];
    }

    /**
     * Dengan guru
     */
    public function withGuru(): static
    {
        return $this->state(fn (array $attributes) => [
            'guru_id' => Pegawai::factory(),
        ]);
    }

    /**
     * Untuk hari tertentu
     */
    public function hari(string $hari): static
    {
        return $this->state(fn (array $attributes) => [
            'hari' => $hari,
        ]);
    }

    /**
     * Tidak aktif
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
