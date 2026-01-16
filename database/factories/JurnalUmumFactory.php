<?php

namespace Database\Factories;

use App\Models\Akun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JurnalUmum>
 */
class JurnalUmumFactory extends Factory
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
            'nomor_bukti' => 'JU-'.fake()->unique()->numerify('######'),
            'tanggal' => fake()->dateTimeBetween('-1 year', 'now'),
            'keterangan' => fake()->sentence(),
            'akun_id' => Akun::factory(),
            'debit' => $isDebit ? fake()->randomFloat(2, 10000, 10000000) : 0,
            'kredit' => ! $isDebit ? fake()->randomFloat(2, 10000, 10000000) : 0,
            'referensi' => fake()->optional()->numerify('REF-######'),
            'jenis_referensi' => fake()->optional()->randomElement(['pembayaran', 'penerimaan', 'penyesuaian']),
            'referensi_id' => null,
            'created_by' => User::factory(),
        ];
    }

    public function debit(?float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'debit' => $amount ?? fake()->randomFloat(2, 10000, 10000000),
            'kredit' => 0,
        ]);
    }

    public function kredit(?float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'debit' => 0,
            'kredit' => $amount ?? fake()->randomFloat(2, 10000, 10000000),
        ]);
    }
}
