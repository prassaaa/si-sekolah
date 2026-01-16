<?php

namespace Database\Factories;

use App\Models\Informasi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Informasi>
 */
class InformasiFactory extends Factory
{
    protected $model = Informasi::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $judul = fake()->sentence(6);
        $kategoriOptions = ['Pengumuman', 'Berita', 'Kegiatan', 'Prestasi', 'Lainnya'];
        $prioritasOptions = ['Rendah', 'Normal', 'Tinggi', 'Urgent'];

        return [
            'judul' => $judul,
            'slug' => Str::slug($judul),
            'kategori' => fake()->randomElement($kategoriOptions),
            'ringkasan' => fake()->paragraph(2),
            'konten' => fake()->paragraphs(5, true),
            'gambar' => null,
            'prioritas' => fake()->randomElement($prioritasOptions),
            'tanggal_publish' => fake()->dateTimeBetween('-1 month', 'now'),
            'tanggal_expired' => fake()->optional(0.3)->dateTimeBetween('now', '+3 months'),
            'is_published' => fake()->boolean(80),
            'is_pinned' => fake()->boolean(20),
            'created_by' => User::inRandomOrder()->first()?->id,
            'views_count' => fake()->numberBetween(0, 500),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'tanggal_publish' => now(),
        ]);
    }

    public function pinned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_pinned' => true,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}
