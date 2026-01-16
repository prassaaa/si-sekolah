<?php

namespace Database\Factories;

use App\Models\Pegawai;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kelulusan>
 */
class KelulusanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['lulus', 'tidak_lulus', 'pending']);

        return [
            'siswa_id' => Siswa::factory(),
            'tahun_ajaran_id' => TahunAjaran::factory(),
            'nomor_ijazah' => $status === 'lulus' ? fake()->numerify('DN-Pd/##/######') : null,
            'nomor_skhun' => $status === 'lulus' ? fake()->numerify('DN-##/######') : null,
            'tanggal_lulus' => fake()->date(),
            'status' => $status,
            'nilai_akhir' => fake()->randomFloat(2, 60, 100),
            'predikat' => $status === 'lulus' ? fake()->randomElement(['sangat_baik', 'baik', 'cukup']) : null,
            'tujuan_sekolah' => $status === 'lulus' ? 'SMP '.fake()->city() : null,
            'catatan' => fake()->optional(0.3)->sentence(),
            'disetujui_oleh' => $status !== 'pending' ? Pegawai::factory() : null,
        ];
    }

    public function lulus(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'lulus',
            'nomor_ijazah' => fake()->numerify('DN-Pd/##/######'),
            'nomor_skhun' => fake()->numerify('DN-##/######'),
            'predikat' => fake()->randomElement(['sangat_baik', 'baik']),
        ]);
    }

    public function tidakLulus(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'tidak_lulus',
            'nomor_ijazah' => null,
            'nomor_skhun' => null,
            'predikat' => null,
            'tujuan_sekolah' => null,
        ]);
    }
}
