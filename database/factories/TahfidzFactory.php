<?php

namespace Database\Factories;

use App\Models\Pegawai;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\Tahfidz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tahfidz>
 */
class TahfidzFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $surahList = array_keys(Tahfidz::surahOptions());
        $surah = fake()->randomElement($surahList);
        $ayatMulai = fake()->numberBetween(1, 20);
        $ayatSelesai = $ayatMulai + fake()->numberBetween(3, 15);

        return [
            'siswa_id' => Siswa::factory(),
            'semester_id' => Semester::factory(),
            'penguji_id' => Pegawai::factory(),
            'surah' => $surah,
            'ayat_mulai' => $ayatMulai,
            'ayat_selesai' => $ayatSelesai,
            'jumlah_ayat' => $ayatSelesai - $ayatMulai + 1,
            'juz' => fake()->numberBetween(1, 30),
            'tanggal' => fake()->dateTimeBetween('-6 months', 'now'),
            'jenis' => fake()->randomElement(['setoran', 'murojaah', 'ujian']),
            'status' => fake()->randomElement(['lulus', 'mengulang', 'pending']),
            'nilai' => fake()->numberBetween(60, 100),
            'catatan' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Status lulus
     */
    public function lulus(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'lulus',
            'nilai' => fake()->numberBetween(75, 100),
        ]);
    }

    /**
     * Status mengulang
     */
    public function mengulang(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'mengulang',
            'nilai' => fake()->numberBetween(40, 65),
        ]);
    }

    /**
     * Status pending
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'nilai' => null,
        ]);
    }

    /**
     * Jenis setoran
     */
    public function setoran(): static
    {
        return $this->state(fn (array $attributes) => [
            'jenis' => 'setoran',
        ]);
    }

    /**
     * Jenis murojaah
     */
    public function murojaah(): static
    {
        return $this->state(fn (array $attributes) => [
            'jenis' => 'murojaah',
        ]);
    }

    /**
     * Jenis ujian
     */
    public function ujian(): static
    {
        return $this->state(fn (array $attributes) => [
            'jenis' => 'ujian',
        ]);
    }

    /**
     * Juz Amma (30)
     */
    public function juzAmma(): static
    {
        $juzAmmaSurah = [
            'An-Naba\'', 'An-Nazi\'at', 'Abasa', 'At-Takwir', 'Al-Infitar',
            'Al-Mutaffifin', 'Al-Insyiqaq', 'Al-Buruj', 'At-Tariq', 'Al-A\'la',
            'Al-Gasyiyah', 'Al-Fajr', 'Al-Balad', 'Asy-Syams', 'Al-Lail',
            'Ad-Duha', 'Asy-Syarh', 'At-Tin', 'Al-Alaq', 'Al-Qadr',
            'Al-Bayyinah', 'Az-Zalzalah', 'Al-Adiyat', 'Al-Qari\'ah', 'At-Takasur',
            'Al-Asr', 'Al-Humazah', 'Al-Fil', 'Quraisy', 'Al-Ma\'un',
            'Al-Kausar', 'Al-Kafirun', 'An-Nasr', 'Al-Lahab', 'Al-Ikhlas',
            'Al-Falaq', 'An-Nas',
        ];

        return $this->state(fn (array $attributes) => [
            'surah' => fake()->randomElement($juzAmmaSurah),
            'juz' => 30,
        ]);
    }
}
