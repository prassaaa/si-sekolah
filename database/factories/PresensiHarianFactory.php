<?php

namespace Database\Factories;

use App\Models\PresensiHarian;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PresensiHarian>
 */
class PresensiHarianFactory extends Factory
{
    protected $model = PresensiHarian::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'siswa_id' => Siswa::factory(),
            'tanggal' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'jam_masuk' => '07:00:00',
            'jam_pulang' => '13:00:00',
            'status' => 'hadir',
            'sumber_masuk' => 'rfid',
            'sumber_pulang' => 'rfid',
            'terlambat_menit' => null,
            'keterangan' => null,
            'dicatat_oleh' => null,
        ];
    }

    public function hadir(): static
    {
        return $this->state(fn () => [
            'status' => 'hadir',
            'jam_masuk' => '06:55:00',
            'terlambat_menit' => null,
        ]);
    }

    public function terlambat(int $menit = 10): static
    {
        return $this->state(fn () => [
            'status' => 'terlambat',
            'jam_masuk' => sprintf('07:%02d:00', $menit),
            'terlambat_menit' => $menit,
        ]);
    }

    public function alpha(): static
    {
        return $this->state(fn () => [
            'status' => 'alpha',
            'jam_masuk' => null,
            'jam_pulang' => null,
            'sumber_masuk' => null,
            'sumber_pulang' => null,
        ]);
    }

    public function izin(): static
    {
        return $this->state(fn () => [
            'status' => 'izin',
            'jam_masuk' => null,
            'jam_pulang' => null,
            'sumber_masuk' => 'manual',
            'sumber_pulang' => null,
            'keterangan' => 'Izin keluarga',
        ]);
    }

    public function sakit(): static
    {
        return $this->state(fn () => [
            'status' => 'sakit',
            'jam_masuk' => null,
            'jam_pulang' => null,
            'sumber_masuk' => 'manual',
            'sumber_pulang' => null,
            'keterangan' => 'Surat dokter terlampir',
        ]);
    }

    public function manualEntry(): static
    {
        return $this->state(fn () => [
            'sumber_masuk' => 'manual',
            'sumber_pulang' => 'manual',
        ]);
    }
}
