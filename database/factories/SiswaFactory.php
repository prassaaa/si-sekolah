<?php

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Siswa>
 */
class SiswaFactory extends Factory
{
    protected $model = Siswa::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $jenisKelamin = fake()->randomElement(['L', 'P']);
        $namaDepan = $jenisKelamin === 'L'
            ? fake()->firstNameMale()
            : fake()->firstNameFemale();

        $alamatSiswa = fake()->streetAddress();
        $kotaSiswa = fake()->city();
        $provinsiSiswa = 'Jawa Barat';

        return [
            // Identitas Utama
            'nis' => fake()->unique()->numerify('######'),
            'nisn' => fake()->unique()->numerify('##########'),
            'nama' => $namaDepan.' '.fake()->lastName(),
            'nama_panggilan' => $namaDepan,
            'jenis_kelamin' => $jenisKelamin,
            'tempat_lahir' => fake()->city(),
            'tanggal_lahir' => fake()->dateTimeBetween('-18 years', '-10 years'),
            'nik' => fake()->numerify('################'),
            'no_kk' => fake()->numerify('################'),
            'no_akta' => fake()->numerify('####/####/####'),
            'agama' => 'Islam',
            'kewarganegaraan' => 'Indonesia',
            'anak_ke' => fake()->numberBetween(1, 5),
            'jumlah_saudara' => fake()->numberBetween(0, 4),

            // Alamat
            'alamat' => $alamatSiswa,
            'rt' => fake()->numerify('0#'),
            'rw' => fake()->numerify('0#'),
            'kelurahan' => fake()->word(),
            'kecamatan' => fake()->word(),
            'kota' => $kotaSiswa,
            'provinsi' => $provinsiSiswa,
            'kode_pos' => fake()->postcode(),

            // Kontak
            'telepon' => fake()->phoneNumber(),
            'hp' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),

            // Data Akademik
            'kelas_id' => null,
            'tanggal_masuk' => fake()->dateTimeBetween('-3 years', 'now'),
            'asal_sekolah' => 'SD '.fake()->company(),
            'status' => 'aktif',
            'tahun_masuk' => fake()->numberBetween(2022, 2025),

            // Data Kesehatan
            'golongan_darah' => fake()->randomElement(['A', 'B', 'AB', 'O', null]),
            'tinggi_badan' => fake()->randomFloat(2, 130, 180),
            'berat_badan' => fake()->randomFloat(2, 30, 80),
            'riwayat_penyakit' => fake()->optional(0.2)->randomElement(['Asma', 'Alergi', 'Maag', null]),

            // Data Ayah
            'nama_ayah' => fake()->name('male'),
            'nik_ayah' => fake()->numerify('################'),
            'tempat_lahir_ayah' => fake()->city(),
            'tanggal_lahir_ayah' => fake()->dateTimeBetween('-60 years', '-35 years'),
            'pendidikan_ayah' => fake()->randomElement(['SD', 'SMP', 'SMA', 'D3', 'S1', 'S2']),
            'pekerjaan_ayah' => fake()->randomElement(['Wiraswasta', 'PNS', 'Karyawan Swasta', 'Guru', 'Pedagang']),
            'penghasilan_ayah' => fake()->randomElement([2000000, 3000000, 5000000, 7000000, 10000000]),
            'telepon_ayah' => fake()->phoneNumber(),
            'alamat_ayah' => $alamatSiswa.', '.$kotaSiswa.', '.$provinsiSiswa,

            // Data Ibu
            'nama_ibu' => fake()->name('female'),
            'nik_ibu' => fake()->numerify('################'),
            'tempat_lahir_ibu' => fake()->city(),
            'tanggal_lahir_ibu' => fake()->dateTimeBetween('-55 years', '-30 years'),
            'pendidikan_ibu' => fake()->randomElement(['SD', 'SMP', 'SMA', 'D3', 'S1', 'S2']),
            'pekerjaan_ibu' => fake()->randomElement(['Ibu Rumah Tangga', 'PNS', 'Guru', 'Wiraswasta', 'Karyawan Swasta']),
            'penghasilan_ibu' => fake()->randomElement([0, 1500000, 2000000, 3000000, 5000000]),
            'telepon_ibu' => fake()->phoneNumber(),
            'alamat_ibu' => $alamatSiswa.', '.$kotaSiswa.', '.$provinsiSiswa,

            // Data Wali (optional - 30% chance)
            'nama_wali' => fake()->optional(0.3)->name(),
            'nik_wali' => fake()->optional(0.3)->numerify('################'),
            'hubungan_wali' => fake()->optional(0.3)->randomElement(['Paman', 'Bibi', 'Kakek', 'Nenek', 'Kakak']),
            'tempat_lahir_wali' => fake()->optional(0.3)->city(),
            'tanggal_lahir_wali' => fake()->optional(0.3)->dateTimeBetween('-65 years', '-25 years'),
            'pendidikan_wali' => fake()->optional(0.3)->randomElement(['SD', 'SMP', 'SMA', 'D3', 'S1', 'S2']),
            'pekerjaan_wali' => fake()->optional(0.3)->randomElement(['Wiraswasta', 'PNS', 'Karyawan Swasta', 'Pedagang']),
            'penghasilan_wali' => fake()->optional(0.3)->randomElement([2000000, 3000000, 5000000, 7000000]),
            'telepon_wali' => fake()->optional(0.3)->phoneNumber(),
            'alamat_wali' => fake()->optional(0.3)->address(),

            // Dokumen (null - karena file upload)
            'foto' => null,
            'foto_kk' => null,
            'foto_akta' => null,
            'foto_ijazah' => null,

            // Keterangan
            'catatan' => fake()->optional(0.1)->sentence(),

            'is_active' => true,
        ];
    }

    /**
     * Dengan kelas tertentu
     */
    public function forKelas(Kelas $kelas): static
    {
        return $this->state(fn (array $attributes) => [
            'kelas_id' => $kelas->id,
        ]);
    }

    /**
     * Siswa laki-laki
     */
    public function lakiLaki(): static
    {
        return $this->state(fn (array $attributes) => [
            'jenis_kelamin' => 'L',
            'nama' => fake()->name('male'),
            'nama_panggilan' => fake()->firstNameMale(),
        ]);
    }

    /**
     * Siswa perempuan
     */
    public function perempuan(): static
    {
        return $this->state(fn (array $attributes) => [
            'jenis_kelamin' => 'P',
            'nama' => fake()->name('female'),
            'nama_panggilan' => fake()->firstNameFemale(),
        ]);
    }

    /**
     * Siswa lulus
     */
    public function lulus(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'lulus',
            'is_active' => false,
        ]);
    }

    /**
     * Siswa pindah
     */
    public function pindah(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pindah',
            'is_active' => false,
        ]);
    }

    /**
     * Siswa tidak aktif
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
