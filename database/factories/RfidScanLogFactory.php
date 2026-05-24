<?php

namespace Database\Factories;

use App\Models\RfidScanLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RfidScanLog>
 */
class RfidScanLogFactory extends Factory
{
    protected $model = RfidScanLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uid' => strtoupper(fake()->unique()->bothify('########')),
            'kartu_rfid_id' => null,
            'owner_type' => null,
            'owner_id' => null,
            'rfid_device_id' => null,
            'jenis' => fake()->randomElement(['masuk', 'pulang', 'duplikat', 'ditolak', 'tidak_dikenal']),
            'pesan' => fake()->sentence(),
            'request_payload' => ['uid' => 'fake', 'scanned_at' => now()->toIso8601String()],
            'response_payload' => ['success' => true],
            'scanned_at' => now(),
        ];
    }

    public function masuk(): static
    {
        return $this->state(fn () => ['jenis' => 'masuk', 'pesan' => 'Tap masuk berhasil']);
    }

    public function pulang(): static
    {
        return $this->state(fn () => ['jenis' => 'pulang', 'pesan' => 'Tap pulang berhasil']);
    }

    public function tidakDikenal(): static
    {
        return $this->state(fn () => [
            'jenis' => 'tidak_dikenal',
            'pesan' => 'Kartu tidak terdaftar',
            'kartu_rfid_id' => null,
            'owner_type' => null,
            'owner_id' => null,
        ]);
    }

    public function ditolak(): static
    {
        return $this->state(fn () => [
            'jenis' => 'ditolak',
            'pesan' => 'Kartu sudah dinonaktifkan',
        ]);
    }

    public function duplikat(): static
    {
        return $this->state(fn () => [
            'jenis' => 'duplikat',
            'pesan' => 'Tap terlalu cepat (debounce)',
        ]);
    }
}
