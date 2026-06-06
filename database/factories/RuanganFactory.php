<?php

namespace Database\Factories;

use App\Models\Pegawai;
use App\Models\Ruangan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ruangan>
 */
class RuanganFactory extends Factory
{
    protected $model = Ruangan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => strtoupper(fake()->unique()->bothify('R-###')),
            'nama' => 'Ruang '.fake()->unique()->numerify('###'),
            'jenis' => fake()->randomElement(['kelas', 'lab', 'kantor', 'gudang', 'perpustakaan', 'aula', 'lainnya']),
            'gedung' => fake()->optional()->randomElement(['Gedung A', 'Gedung B', 'Gedung C']),
            'lantai' => fake()->optional()->numberBetween(1, 4),
            'kapasitas' => fake()->optional()->numberBetween(10, 50),
            'penanggung_jawab_id' => null,
            'keterangan' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function denganPenanggungJawab(): static
    {
        return $this->state(fn () => ['penanggung_jawab_id' => Pegawai::factory()]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
