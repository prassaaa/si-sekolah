<?php

namespace Database\Factories;

use App\Models\MataPelajaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MataPelajaran>
 */
class MataPelajaranFactory extends Factory
{
    protected $model = MataPelajaran::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mapel = fake()->randomElement([
            ['kode' => 'MTK', 'nama' => 'Matematika', 'singkatan' => 'MTK', 'kelompok' => 'Kelompok A'],
            ['kode' => 'BIN', 'nama' => 'Bahasa Indonesia', 'singkatan' => 'BIN', 'kelompok' => 'Kelompok A'],
            ['kode' => 'IPA', 'nama' => 'Ilmu Pengetahuan Alam', 'singkatan' => 'IPA', 'kelompok' => 'Kelompok A'],
            ['kode' => 'IPS', 'nama' => 'Ilmu Pengetahuan Sosial', 'singkatan' => 'IPS', 'kelompok' => 'Kelompok A'],
            ['kode' => 'PAI', 'nama' => 'Pendidikan Agama Islam', 'singkatan' => 'PAI', 'kelompok' => 'Kelompok A'],
            ['kode' => 'PKN', 'nama' => 'Pendidikan Kewarganegaraan', 'singkatan' => 'PKn', 'kelompok' => 'Kelompok A'],
            ['kode' => 'BING', 'nama' => 'Bahasa Inggris', 'singkatan' => 'BING', 'kelompok' => 'Kelompok B'],
            ['kode' => 'PJOK', 'nama' => 'Pendidikan Jasmani', 'singkatan' => 'PJOK', 'kelompok' => 'Kelompok B'],
        ]);

        return [
            'kode' => $mapel['kode'].fake()->unique()->numberBetween(1, 999),
            'nama' => $mapel['nama'],
            'singkatan' => $mapel['singkatan'],
            'kelompok' => $mapel['kelompok'],
            'jenjang' => fake()->randomElement(['SD', 'SMP', 'SMA']),
            'jam_per_minggu' => fake()->numberBetween(2, 6),
            'kkm' => fake()->randomElement([70, 75, 80]),
            'urutan' => fake()->numberBetween(1, 20),
            'is_active' => true,
            'deskripsi' => fake()->optional()->sentence(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
