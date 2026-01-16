<?php

namespace Database\Factories;

use App\Models\JabatanPegawai;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JabatanPegawai>
 */
class JabatanPegawaiFactory extends Factory
{
    protected $model = JabatanPegawai::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jenisOptions = ['Struktural', 'Fungsional', 'Non-Fungsional'];
        $golonganOptions = ['I', 'II', 'III', 'IV', 'Non-PNS'];

        return [
            'kode' => fake()->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'nama' => fake()->jobTitle(),
            'jenis' => fake()->randomElement($jenisOptions),
            'golongan' => fake()->randomElement($golonganOptions),
            'gaji_pokok' => fake()->numberBetween(2000000, 15000000),
            'tunjangan' => fake()->numberBetween(500000, 5000000),
            'deskripsi' => fake()->sentence(10),
            'urutan' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function struktural(): static
    {
        return $this->state(fn (array $attributes) => [
            'jenis' => 'Struktural',
        ]);
    }

    public function fungsional(): static
    {
        return $this->state(fn (array $attributes) => [
            'jenis' => 'Fungsional',
        ]);
    }
}
