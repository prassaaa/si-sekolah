<?php

namespace Database\Factories;

use App\Models\JenisPembayaran;
use App\Models\Semester;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TagihanSiswa>
 */
class TagihanSiswaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nominal = fake()->randomElement([150000, 250000, 500000, 750000]);
        $diskon = fake()->optional(0.2)->randomElement([0, 25000, 50000]) ?? 0;
        $totalTagihan = $nominal - $diskon;
        $status = fake()->randomElement(['belum_bayar', 'sebagian', 'lunas']);
        $totalTerbayar = match ($status) {
            'lunas' => $totalTagihan,
            'sebagian' => (int) ($totalTagihan * 0.5),
            default => 0,
        };

        return [
            'siswa_id' => Siswa::factory(),
            'jenis_pembayaran_id' => JenisPembayaran::factory(),
            'semester_id' => Semester::factory(),
            'nomor_tagihan' => 'TGH-'.fake()->unique()->numerify('######'),
            'nominal' => $nominal,
            'diskon' => $diskon,
            'total_tagihan' => $totalTagihan,
            'total_terbayar' => $totalTerbayar,
            'sisa_tagihan' => $totalTagihan - $totalTerbayar,
            'tanggal_tagihan' => fake()->dateTimeBetween('-3 months', 'now'),
            'tanggal_jatuh_tempo' => fake()->dateTimeBetween('now', '+1 month'),
            'status' => $status,
            'keterangan' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function lunas(): static
    {
        return $this->state(function (array $attributes) {
            $totalTagihan = $attributes['total_tagihan'] ?? 250000;

            return [
                'status' => 'lunas',
                'total_terbayar' => $totalTagihan,
                'sisa_tagihan' => 0,
            ];
        });
    }

    public function belumBayar(): static
    {
        return $this->state(function (array $attributes) {
            $totalTagihan = $attributes['total_tagihan'] ?? 250000;

            return [
                'status' => 'belum_bayar',
                'total_terbayar' => 0,
                'sisa_tagihan' => $totalTagihan,
            ];
        });
    }
}
