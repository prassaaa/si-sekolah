<?php

namespace Database\Factories;

use App\Models\Akun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Akun>
 */
class AkunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tipe = fake()->randomElement(['aset', 'liabilitas', 'ekuitas', 'pendapatan', 'beban']);
        $posisiNormal = in_array($tipe, ['aset', 'beban']) ? 'debit' : 'kredit';

        return [
            'kode' => fake()->unique()->numerify('###-##-##'),
            'nama' => fake()->words(3, true),
            'tipe' => $tipe,
            'kategori' => fake()->randomElement(['lancar', 'tetap', 'jangka_panjang', 'operasional', 'non_operasional']),
            'parent_id' => null,
            'deskripsi' => fake()->optional()->sentence(),
            'saldo_awal' => fake()->randomFloat(2, 0, 100000000),
            'saldo_akhir' => fake()->randomFloat(2, 0, 100000000),
            'posisi_normal' => $posisiNormal,
            'is_active' => true,
            'level' => 1,
        ];
    }

    public function aset(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipe' => 'aset',
            'posisi_normal' => 'debit',
        ]);
    }

    public function pendapatan(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipe' => 'pendapatan',
            'posisi_normal' => 'kredit',
        ]);
    }

    public function beban(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipe' => 'beban',
            'posisi_normal' => 'debit',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withParent(Akun $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
            'level' => $parent->level + 1,
        ]);
    }
}
