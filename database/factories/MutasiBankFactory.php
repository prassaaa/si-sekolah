<?php

namespace Database\Factories;

use App\Models\Akun;
use App\Models\MutasiBank;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MutasiBank>
 */
class MutasiBankFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isDebit = fake()->boolean();

        return [
            'akun_id' => Akun::factory(),
            'tanggal' => fake()->dateTimeBetween('-1 month', 'now'),
            'keterangan' => fake()->sentence(3),
            'debit' => $isDebit ? fake()->randomFloat(2, 10000, 5000000) : 0,
            'kredit' => ! $isDebit ? fake()->randomFloat(2, 10000, 5000000) : 0,
            'saldo' => null,
            'is_matched' => false,
            'jurnal_umum_id' => null,
        ];
    }

    public function debit(?float $amount = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'debit' => $amount ?? fake()->randomFloat(2, 10000, 5000000),
            'kredit' => 0,
        ]);
    }

    public function kredit(?float $amount = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'debit' => 0,
            'kredit' => $amount ?? fake()->randomFloat(2, 10000, 5000000),
        ]);
    }

    public function matched(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_matched' => true,
        ]);
    }
}
