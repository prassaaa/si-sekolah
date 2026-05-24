<?php

namespace Database\Factories;

use App\Models\Pegawai;
use App\Models\PresensiHarianPegawai;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PresensiHarianPegawai>
 */
class PresensiHarianPegawaiFactory extends Factory
{
    protected $model = PresensiHarianPegawai::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pegawai_id' => Pegawai::factory(),
            'tanggal' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'jam_masuk' => '07:00:00',
            'jam_pulang' => '15:00:00',
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
            'jam_masuk' => '06:50:00',
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

    public function cuti(): static
    {
        return $this->state(fn () => [
            'status' => 'cuti',
            'jam_masuk' => null,
            'jam_pulang' => null,
            'sumber_masuk' => 'manual',
            'sumber_pulang' => null,
            'keterangan' => 'Cuti tahunan',
        ]);
    }

    public function dinasLuar(): static
    {
        return $this->state(fn () => [
            'status' => 'dinas_luar',
            'jam_masuk' => null,
            'jam_pulang' => null,
            'sumber_masuk' => 'manual',
            'sumber_pulang' => null,
            'keterangan' => 'Dinas luar kota',
        ]);
    }
}
