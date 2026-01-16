<?php

namespace Database\Factories;

use App\Models\Semester;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Prestasi>
 */
class PrestasiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'siswa_id' => Siswa::factory(),
            'semester_id' => Semester::factory(),
            'nama_prestasi' => fake()->randomElement(['Olimpiade Matematika', 'Lomba Cerdas Cermat', 'Kompetisi Sains', 'Lomba Pidato', 'Kejuaraan Futsal', 'Festival Seni']),
            'tingkat' => fake()->randomElement(['sekolah', 'kecamatan', 'kabupaten', 'provinsi', 'nasional']),
            'jenis' => fake()->randomElement(['akademik', 'non_akademik', 'olahraga', 'seni', 'keagamaan']),
            'peringkat' => fake()->randomElement(['juara_1', 'juara_2', 'juara_3', 'harapan_1', 'peserta']),
            'penyelenggara' => fake()->company(),
            'tanggal' => fake()->dateTimeBetween('-1 year', 'now'),
            'bukti' => null,
            'keterangan' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function juara(): static
    {
        return $this->state(fn (array $attributes) => [
            'peringkat' => fake()->randomElement(['juara_1', 'juara_2', 'juara_3']),
        ]);
    }

    public function nasional(): static
    {
        return $this->state(fn (array $attributes) => [
            'tingkat' => 'nasional',
        ]);
    }
}
