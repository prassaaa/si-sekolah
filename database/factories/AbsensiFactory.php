<?php

namespace Database\Factories;

use App\Models\Absensi;
use App\Models\JadwalPelajaran;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Absensi> */
class AbsensiFactory extends Factory
{
    protected $model = Absensi::class;

    public function definition(): array
    {
        return [
            'jadwal_pelajaran_id' => JadwalPelajaran::factory(),
            'siswa_id' => Siswa::factory(),
            'tanggal' => fake()->date(),
            'status' => 'hadir',
            'keterangan' => null,
        ];
    }

    /** Status: hadir */
    public function hadir(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'hadir']);
    }

    /** Status: sakit */
    public function sakit(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sakit',
            'keterangan' => fake()->sentence(),
        ]);
    }

    /** Status: izin */
    public function izin(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'izin',
            'keterangan' => fake()->sentence(),
        ]);
    }

    /** Status: alpha */
    public function alpha(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'alpha']);
    }
}
