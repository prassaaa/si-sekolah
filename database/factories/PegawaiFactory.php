<?php

namespace Database\Factories;

use App\Models\JabatanPegawai;
use App\Models\Pegawai;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pegawai>
 */
class PegawaiFactory extends Factory
{
    protected $model = Pegawai::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jenisKelamin = fake()->randomElement(['L', 'P']);
        $statusKepegawaian = ['PNS', 'PPPK', 'GTY', 'GTT', 'PTY', 'PTT'];
        $pendidikan = ['D3', 'D4', 'S1', 'S2', 'S3'];
        $agama = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
        $statusPernikahan = ['Belum Menikah', 'Menikah', 'Cerai'];

        return [
            'nip' => fake()->optional(0.6)->numerify('##################'),
            'nuptk' => fake()->optional(0.7)->numerify('################'),
            'nama' => $jenisKelamin === 'L' ? fake()->name('male') : fake()->name('female'),
            'jenis_kelamin' => $jenisKelamin,
            'tempat_lahir' => fake()->city(),
            'tanggal_lahir' => fake()->dateTimeBetween('-55 years', '-22 years'),
            'agama' => fake()->randomElement($agama),
            'alamat' => fake()->address(),
            'telepon' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'foto' => null,
            'jabatan_id' => JabatanPegawai::inRandomOrder()->first()?->id,
            'user_id' => null,
            'status_kepegawaian' => fake()->randomElement($statusKepegawaian),
            'pendidikan_terakhir' => fake()->randomElement($pendidikan),
            'jurusan' => fake()->randomElement(['Pendidikan', 'Matematika', 'Bahasa Indonesia', 'IPA', 'IPS', 'Agama Islam']),
            'universitas' => 'Universitas '.fake()->city(),
            'tahun_lulus' => fake()->year(),
            'tanggal_masuk' => fake()->dateTimeBetween('-15 years', '-1 year'),
            'tanggal_keluar' => null,
            'no_rekening' => fake()->numerify('##########'),
            'nama_bank' => fake()->randomElement(['BCA', 'BRI', 'BNI', 'Mandiri', 'BSI']),
            'npwp' => fake()->numerify('##.###.###.#-###.###'),
            'no_bpjs_kesehatan' => fake()->numerify('#############'),
            'no_bpjs_ketenagakerjaan' => fake()->numerify('###########'),
            'status_pernikahan' => fake()->randomElement($statusPernikahan),
            'jumlah_tanggungan' => fake()->numberBetween(0, 5),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'tanggal_keluar' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function pns(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_kepegawaian' => 'PNS',
            'nip' => fake()->numerify('##################'),
        ]);
    }

    public function honorer(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_kepegawaian' => 'GTT',
            'nip' => null,
        ]);
    }
}
