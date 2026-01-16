<?php

namespace Database\Factories;

use App\Models\Pegawai;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IzinPulang>
 */
class IzinPulangFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'siswa_id' => Siswa::factory(),
            'tanggal' => fake()->dateTimeBetween('-1 month', 'now'),
            'jam_pulang' => fake()->dateTimeBetween('09:00', '14:00')->format('H:i'),
            'alasan' => fake()->randomElement(['Sakit perut', 'Demam', 'Pusing', 'Acara keluarga', 'Keperluan mendesak']),
            'kategori' => fake()->randomElement(['sakit', 'kepentingan_keluarga', 'urusan_pribadi', 'lainnya']),
            'penjemput_nama' => fake()->optional(0.8)->name(),
            'penjemput_hubungan' => fake()->optional(0.8)->randomElement(['Ayah', 'Ibu', 'Kakak', 'Paman', 'Bibi', 'Wali']),
            'penjemput_telepon' => fake()->optional(0.8)->phoneNumber(),
            'petugas_id' => Pegawai::factory(),
            'status' => fake()->randomElement(['diizinkan', 'ditolak', 'pending']),
            'catatan' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Kategori sakit
     */
    public function sakit(): static
    {
        return $this->state(fn (array $attributes) => [
            'kategori' => 'sakit',
            'alasan' => fake()->randomElement(['Demam', 'Sakit perut', 'Pusing', 'Mual', 'Lemas']),
        ]);
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
     * Hari ini
     */
    public function hariIni(): static
    {
        return $this->state(fn (array $attributes) => [
            'tanggal' => today(),
        ]);
    }
}
