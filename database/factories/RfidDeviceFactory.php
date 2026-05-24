<?php

namespace Database\Factories;

use App\Models\RfidDevice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<RfidDevice>
 */
class RfidDeviceFactory extends Factory
{
    protected $model = RfidDevice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jenis = fake()->randomElement(['gerbang_masuk', 'gerbang_pulang', 'serbaguna']);

        return [
            'nama' => 'Reader '.fake()->unique()->words(2, true),
            'kode' => strtoupper(Str::slug(fake()->unique()->word().'-'.fake()->numberBetween(1, 99))),
            'jenis' => $jenis,
            'lokasi' => fake()->randomElement(['Gerbang Utama', 'Gerbang Belakang', 'Lobi', 'Pos Satpam']),
            'api_token' => Hash::make(Str::random(60)),
            'terakhir_aktif' => null,
            'is_active' => true,
            'keterangan' => null,
        ];
    }

    public function masuk(): static
    {
        return $this->state(fn () => ['jenis' => 'gerbang_masuk']);
    }

    public function pulang(): static
    {
        return $this->state(fn () => ['jenis' => 'gerbang_pulang']);
    }

    public function nonaktif(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
