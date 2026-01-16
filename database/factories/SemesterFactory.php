<?php

namespace Database\Factories;

use App\Models\Semester;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Semester>
 */
class SemesterFactory extends Factory
{
    protected $model = Semester::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $semester = fake()->randomElement([1, 2]);
        $tahunAjaran = TahunAjaran::inRandomOrder()->first() ?? TahunAjaran::factory()->create();

        return [
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => $semester,
            'nama' => ($semester === 1 ? 'Semester Ganjil ' : 'Semester Genap ').$tahunAjaran->kode,
            'tanggal_mulai' => $semester === 1 ? $tahunAjaran->tanggal_mulai : $tahunAjaran->tanggal_mulai->addMonths(6),
            'tanggal_selesai' => $semester === 1 ? $tahunAjaran->tanggal_mulai->addMonths(5) : $tahunAjaran->tanggal_selesai,
            'is_active' => false,
            'keterangan' => fake()->optional()->sentence(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    public function ganjil(): static
    {
        return $this->state(function (array $attributes): array {
            $tahunAjaran = TahunAjaran::find($attributes['tahun_ajaran_id']);

            return [
                'semester' => 1,
                'nama' => 'Semester Ganjil '.$tahunAjaran->kode,
            ];
        });
    }

    public function genap(): static
    {
        return $this->state(function (array $attributes): array {
            $tahunAjaran = TahunAjaran::find($attributes['tahun_ajaran_id']);

            return [
                'semester' => 2,
                'nama' => 'Semester Genap '.$tahunAjaran->kode,
            ];
        });
    }
}
