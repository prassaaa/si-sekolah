<?php

namespace Database\Factories;

use App\Models\KartuRfid;
use App\Models\Pegawai;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KartuRfid>
 */
class KartuRfidFactory extends Factory
{
    protected $model = KartuRfid::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_type' => Siswa::class,
            'owner_id' => Siswa::factory(),
            'uid' => strtoupper(fake()->unique()->bothify('########')),
            'status' => 'aktif',
            'diaktifkan_pada' => now(),
            'dinonaktifkan_pada' => null,
            'keterangan' => null,
        ];
    }

    public function aktif(): static
    {
        return $this->state(fn () => [
            'status' => 'aktif',
            'dinonaktifkan_pada' => null,
        ]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn () => [
            'status' => 'nonaktif',
            'dinonaktifkan_pada' => now(),
        ]);
    }

    public function hilang(): static
    {
        return $this->state(fn () => [
            'status' => 'hilang',
            'dinonaktifkan_pada' => now(),
            'keterangan' => 'Kartu hilang',
        ]);
    }

    public function rusak(): static
    {
        return $this->state(fn () => [
            'status' => 'rusak',
            'dinonaktifkan_pada' => now(),
            'keterangan' => 'Kartu rusak',
        ]);
    }

    public function untukPegawai(): static
    {
        return $this->state(fn () => [
            'owner_type' => Pegawai::class,
            'owner_id' => Pegawai::factory(),
        ]);
    }
}
