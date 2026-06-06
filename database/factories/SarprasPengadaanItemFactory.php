<?php

namespace Database\Factories;

use App\Models\SarprasKategori;
use App\Models\SarprasPengadaan;
use App\Models\SarprasPengadaanItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SarprasPengadaanItem>
 */
class SarprasPengadaanItemFactory extends Factory
{
    protected $model = SarprasPengadaanItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jumlah = fake()->numberBetween(1, 20);
        $hargaSatuan = fake()->randomElement([50000, 100000, 250000, 500000]);

        return [
            'sarpras_pengadaan_id' => SarprasPengadaan::factory(),
            'nama_barang' => fake()->randomElement(['Spidol', 'Kertas A4', 'Laptop', 'Kursi Lipat', 'Lampu']).' '.fake()->numerify('##'),
            'sarpras_kategori_id' => SarprasKategori::factory(),
            'jumlah' => $jumlah,
            'satuan' => fake()->randomElement(['unit', 'box', 'pak', 'buah']),
            'harga_satuan' => $hargaSatuan,
            'subtotal' => bcmul((string) $jumlah, (string) $hargaSatuan, 2),
        ];
    }
}
