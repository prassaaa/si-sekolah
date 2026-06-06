<?php

namespace Database\Factories;

use App\Models\Ruangan;
use App\Models\SarprasBarang;
use App\Models\SarprasKategori;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SarprasBarang>
 */
class SarprasBarangFactory extends Factory
{
    protected $model = SarprasBarang::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode_inventaris' => strtoupper(fake()->unique()->bothify('INV-####-???')),
            'nama' => fake()->randomElement(['Laptop', 'Proyektor', 'Meja', 'Kursi', 'Lemari', 'Printer']).' '.fake()->unique()->numerify('###'),
            'sarpras_kategori_id' => SarprasKategori::factory(),
            'ruangan_id' => Ruangan::factory(),
            'tipe' => 'aset',
            'merk' => fake()->optional()->company(),
            'spesifikasi' => fake()->optional()->sentence(),
            'kondisi' => 'baik',
            'status' => 'tersedia',
            'sumber_dana' => fake()->randomElement(['bos', 'komite', 'yayasan', 'hibah', 'pribadi', 'lainnya']),
            'tahun_perolehan' => fake()->numberBetween(2015, 2025),
            'harga_perolehan' => fake()->randomElement([500000, 1000000, 2500000, 5000000]),
            'jumlah' => 1,
            'satuan' => 'unit',
            'foto' => null,
            'keterangan' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function aset(): static
    {
        return $this->state(fn () => ['tipe' => 'aset', 'jumlah' => 1]);
    }

    public function bahan(): static
    {
        return $this->state(fn () => [
            'tipe' => 'bahan',
            'jumlah' => fake()->numberBetween(10, 100),
            'satuan' => fake()->randomElement(['buah', 'box', 'pak', 'rim']),
        ]);
    }

    public function tersedia(): static
    {
        return $this->state(fn () => ['status' => 'tersedia']);
    }

    public function dipinjam(): static
    {
        return $this->state(fn () => ['status' => 'dipinjam']);
    }

    public function rusak(): static
    {
        return $this->state(fn () => ['kondisi' => 'rusak_berat']);
    }
}
