<?php

namespace Database\Factories;

use App\Models\SarprasBarang;
use App\Models\SarprasPemeliharaan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SarprasPemeliharaan>
 */
class SarprasPemeliharaanFactory extends Factory
{
    protected $model = SarprasPemeliharaan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sarpras_barang_id' => SarprasBarang::factory(),
            'jenis' => fake()->randomElement(['rutin', 'perbaikan', 'kalibrasi']),
            'tanggal' => fake()->dateTimeBetween('-2 months', 'now'),
            'tanggal_selesai' => null,
            'deskripsi_masalah' => fake()->sentence(),
            'tindakan' => fake()->optional()->sentence(),
            'pelaksana' => 'internal',
            'nama_vendor' => null,
            'biaya' => fake()->randomElement([0, 50000, 150000, 300000]),
            'kondisi_sebelum' => 'rusak_ringan',
            'kondisi_sesudah' => null,
            'status' => 'dijadwalkan',
            'dicatat_oleh' => User::factory(),
        ];
    }

    public function proses(): static
    {
        return $this->state(fn () => ['status' => 'proses']);
    }

    public function selesai(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'selesai',
            'tanggal_selesai' => now(),
            'kondisi_sesudah' => 'baik',
            'tindakan' => 'Perbaikan selesai dilakukan.',
        ]);
    }

    public function vendor(): static
    {
        return $this->state(fn () => [
            'pelaksana' => 'vendor',
            'nama_vendor' => fake()->company(),
        ]);
    }
}
