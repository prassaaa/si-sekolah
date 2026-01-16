<?php

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\Pegawai;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kelas>
 */
class KelasFactory extends Factory
{
    protected $model = Kelas::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tingkat = fake()->numberBetween(1, 12);
        $suffix = fake()->randomElement(['A', 'B', 'C', 'D']);

        return [
            'tahun_ajaran_id' => TahunAjaran::factory(),
            'nama' => $tingkat.$suffix,
            'tingkat' => $tingkat,
            'jurusan' => $tingkat >= 10 ? fake()->randomElement(['IPA', 'IPS', 'Bahasa', null]) : null,
            'wali_kelas_id' => null,
            'kapasitas' => fake()->numberBetween(25, 40),
            'ruangan' => 'Ruang '.$tingkat.$suffix,
            'urutan' => fake()->numberBetween(1, 100),
            'is_active' => true,
            'keterangan' => null,
        ];
    }

    /**
     * Dengan wali kelas
     */
    public function withWaliKelas(): static
    {
        return $this->state(fn (array $attributes) => [
            'wali_kelas_id' => Pegawai::factory(),
        ]);
    }

    /**
     * Kelas SD (tingkat 1-6)
     */
    public function sd(): static
    {
        return $this->state(function (array $attributes) {
            $tingkat = fake()->numberBetween(1, 6);
            $suffix = fake()->randomElement(['A', 'B', 'C']);

            return [
                'nama' => $tingkat.$suffix,
                'tingkat' => $tingkat,
                'jurusan' => null,
                'ruangan' => 'Ruang SD '.$tingkat.$suffix,
            ];
        });
    }

    /**
     * Kelas SMP (tingkat 7-9)
     */
    public function smp(): static
    {
        return $this->state(function (array $attributes) {
            $tingkat = fake()->numberBetween(7, 9);
            $suffix = fake()->randomElement(['A', 'B', 'C', 'D']);

            return [
                'nama' => $tingkat.$suffix,
                'tingkat' => $tingkat,
                'jurusan' => null,
                'ruangan' => 'Ruang SMP '.$tingkat.$suffix,
            ];
        });
    }

    /**
     * Kelas SMA (tingkat 10-12)
     */
    public function sma(): static
    {
        return $this->state(function (array $attributes) {
            $tingkat = fake()->numberBetween(10, 12);
            $jurusan = fake()->randomElement(['IPA', 'IPS']);
            $suffix = fake()->randomElement(['1', '2', '3']);

            return [
                'nama' => 'X'.($tingkat - 9).' '.$jurusan.' '.$suffix,
                'tingkat' => $tingkat,
                'jurusan' => $jurusan,
                'ruangan' => 'Ruang SMA '.($tingkat - 9).$jurusan.$suffix,
            ];
        });
    }

    /**
     * Kelas tidak aktif
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Untuk tahun ajaran tertentu
     */
    public function forTahunAjaran(TahunAjaran $tahunAjaran): static
    {
        return $this->state(fn (array $attributes) => [
            'tahun_ajaran_id' => $tahunAjaran->id,
        ]);
    }
}
