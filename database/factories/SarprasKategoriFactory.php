<?php

namespace Database\Factories;

use App\Models\SarprasKategori;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SarprasKategori>
 */
class SarprasKategoriFactory extends Factory
{
    protected $model = SarprasKategori::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => strtoupper(fake()->unique()->bothify('???')),
            'nama' => fake()->randomElement(['Elektronik', 'Mebel', 'Alat Laboratorium', 'Alat Olahraga', 'Buku']).' '.fake()->unique()->numerify('##'),
            'deskripsi' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
