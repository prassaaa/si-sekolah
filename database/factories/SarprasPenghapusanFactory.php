<?php

namespace Database\Factories;

use App\Models\SarprasBarang;
use App\Models\SarprasPenghapusan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SarprasPenghapusan>
 */
class SarprasPenghapusanFactory extends Factory
{
    protected $model = SarprasPenghapusan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sarpras_barang_id' => SarprasBarang::factory()->rusak(),
            'tanggal' => fake()->dateTimeBetween('-2 months', 'now'),
            'alasan' => fake()->randomElement(['rusak_berat', 'hilang', 'usang', 'lainnya']),
            'jumlah' => 1,
            'nilai_sisa' => fake()->randomElement([0, 50000, 100000]),
            'metode' => fake()->randomElement(['dibuang', 'dijual', 'disumbangkan']),
            'disetujui_oleh' => User::factory(),
            'status' => 'diajukan',
            'keterangan' => fake()->optional()->sentence(),
        ];
    }

    public function diajukan(): static
    {
        return $this->state(fn () => ['status' => 'diajukan']);
    }

    public function disetujui(): static
    {
        return $this->state(fn () => ['status' => 'disetujui']);
    }

    public function ditolak(): static
    {
        return $this->state(fn () => ['status' => 'ditolak']);
    }
}
