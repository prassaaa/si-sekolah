<?php

namespace Database\Factories;

use App\Models\Sekolah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sekolah>
 */
class SekolahFactory extends Factory
{
    protected $model = Sekolah::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jenjangOptions = ['TK', 'SD', 'SMP', 'SMA', 'SMK', 'MA', 'MI', 'MTs', 'RA'];
        $jenjang = fake()->randomElement($jenjangOptions);

        return [
            'npsn' => fake()->unique()->numerify('########'),
            'nama' => $jenjang.' '.fake()->company(),
            'nama_yayasan' => 'Yayasan '.fake()->lastName(),
            'jenjang' => $jenjang,
            'status' => fake()->randomElement(['Negeri', 'Swasta']),
            'alamat' => fake()->streetAddress(),
            'kelurahan' => fake()->citySuffix(),
            'kecamatan' => fake()->city(),
            'kabupaten' => fake()->city(),
            'provinsi' => fake()->state(),
            'kode_pos' => fake()->postcode(),
            'telepon' => fake()->phoneNumber(),
            'fax' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'website' => fake()->url(),
            'kepala_sekolah' => fake()->name(),
            'nip_kepala_sekolah' => fake()->numerify('##################'),
            'logo' => null,
            'visi' => fake()->sentence(10),
            'misi' => fake()->paragraph(3),
            'tahun_berdiri' => fake()->year(),
            'akreditasi' => fake()->randomElement(['A', 'B', 'C', 'TT']),
            'tanggal_akreditasi' => fake()->date(),
            'no_sk_operasional' => fake()->numerify('SK/###/####/####'),
            'tanggal_sk_operasional' => fake()->date(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
