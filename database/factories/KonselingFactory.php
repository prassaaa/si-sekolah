<?php

namespace Database\Factories;

use App\Models\Pegawai;
use App\Models\Semester;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Konseling>
 */
class KonselingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'siswa_id' => Siswa::factory(),
            'semester_id' => Semester::factory(),
            'konselor_id' => Pegawai::factory(),
            'tanggal' => fake()->dateTimeBetween('-3 months', 'now'),
            'waktu_mulai' => fake()->time('H:i'),
            'waktu_selesai' => fake()->time('H:i'),
            'jenis' => fake()->randomElement(['individu', 'kelompok']),
            'kategori' => fake()->randomElement(['akademik', 'pribadi', 'sosial', 'karir']),
            'permasalahan' => fake()->paragraph(),
            'hasil_konseling' => fake()->optional(0.7)->paragraph(),
            'rekomendasi' => fake()->optional(0.5)->sentence(),
            'status' => fake()->randomElement(['dijadwalkan', 'selesai']),
            'perlu_tindak_lanjut' => fake()->boolean(30),
            'tanggal_tindak_lanjut' => fake()->optional(0.3)->dateTimeBetween('now', '+1 month'),
            'catatan' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function selesai(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'selesai',
            'hasil_konseling' => fake()->paragraph(),
        ]);
    }

    public function dijadwalkan(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'dijadwalkan',
            'tanggal' => fake()->dateTimeBetween('now', '+1 week'),
        ]);
    }
}
