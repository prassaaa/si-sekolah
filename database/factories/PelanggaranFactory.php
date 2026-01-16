<?php

namespace Database\Factories;

use App\Models\Pegawai;
use App\Models\Semester;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pelanggaran>
 */
class PelanggaranFactory extends Factory
{
    public function definition(): array
    {
        $kategori = fake()->randomElement(['ringan', 'sedang', 'berat']);
        $poin = match ($kategori) {
            'ringan' => fake()->numberBetween(5, 15),
            'sedang' => fake()->numberBetween(20, 40),
            'berat' => fake()->numberBetween(50, 100),
        };

        return [
            'siswa_id' => Siswa::factory(),
            'semester_id' => Semester::factory(),
            'tanggal' => fake()->dateTimeBetween('-6 months', 'now'),
            'jenis_pelanggaran' => fake()->randomElement(['Terlambat masuk', 'Tidak berseragam', 'Bolos', 'Berkelahi', 'Merokok', 'Bullying', 'Tidak mengerjakan PR']),
            'kategori' => $kategori,
            'poin' => $poin,
            'deskripsi' => fake()->optional(0.5)->sentence(),
            'bukti' => null,
            'pelapor_id' => Pegawai::factory(),
            'status' => fake()->randomElement(['proses', 'selesai']),
            'tindak_lanjut' => fake()->optional(0.5)->randomElement(['Peringatan lisan', 'Peringatan tertulis', 'Panggilan orang tua', 'Skorsing']),
            'catatan' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function ringan(): static
    {
        return $this->state(fn (array $attributes) => [
            'kategori' => 'ringan',
            'poin' => fake()->numberBetween(5, 15),
        ]);
    }

    public function berat(): static
    {
        return $this->state(fn (array $attributes) => [
            'kategori' => 'berat',
            'poin' => fake()->numberBetween(50, 100),
        ]);
    }
}
