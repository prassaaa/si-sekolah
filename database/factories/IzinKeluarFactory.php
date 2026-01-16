<?php

namespace Database\Factories;

use App\Models\Pegawai;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IzinKeluar>
 */
class IzinKeluarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jamKeluar = fake()->dateTimeBetween('08:00', '12:00');
        $jamKembali = fake()->optional(0.7)->dateTimeBetween('10:00', '14:00');

        return [
            'siswa_id' => Siswa::factory(),
            'tanggal' => fake()->dateTimeBetween('-1 month', 'now'),
            'jam_keluar' => $jamKeluar->format('H:i'),
            'jam_kembali' => $jamKembali?->format('H:i'),
            'keperluan' => fake()->randomElement(['Berobat ke dokter', 'Keperluan keluarga', 'Mengurus dokumen', 'Acara keluarga', 'Sakit']),
            'tujuan' => fake()->optional()->city(),
            'penjemput_nama' => fake()->optional(0.8)->name(),
            'penjemput_hubungan' => fake()->optional(0.8)->randomElement(['Ayah', 'Ibu', 'Kakak', 'Paman', 'Bibi', 'Wali']),
            'penjemput_telepon' => fake()->optional(0.8)->phoneNumber(),
            'petugas_id' => Pegawai::factory(),
            'status' => fake()->randomElement(['diizinkan', 'ditolak', 'pending']),
            'catatan' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Status diizinkan
     */
    public function diizinkan(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'diizinkan',
        ]);
    }

    /**
     * Status pending
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Hari ini
     */
    public function hariIni(): static
    {
        return $this->state(fn (array $attributes) => [
            'tanggal' => today(),
        ]);
    }
}
