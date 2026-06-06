<?php

namespace Database\Factories;

use App\Models\Pegawai;
use App\Models\SarprasBarang;
use App\Models\SarprasPeminjaman;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<SarprasPeminjaman>
 */
class SarprasPeminjamanFactory extends Factory
{
    protected $model = SarprasPeminjaman::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tanggalPinjam = Carbon::parse(fake()->dateTimeBetween('-1 month', 'now'));

        return [
            'sarpras_barang_id' => SarprasBarang::factory()->aset()->tersedia(),
            'peminjam_type' => Siswa::class,
            'peminjam_id' => Siswa::factory(),
            'jumlah' => 1,
            'tanggal_pinjam' => $tanggalPinjam,
            'tanggal_harus_kembali' => $tanggalPinjam->copy()->addDays(7),
            'tanggal_kembali' => null,
            'kondisi_pinjam' => 'baik',
            'kondisi_kembali' => null,
            'status' => 'dipinjam',
            'petugas_id' => Pegawai::factory(),
            'catatan' => fake()->optional()->sentence(),
        ];
    }

    public function dipinjam(): static
    {
        return $this->state(fn () => [
            'status' => 'dipinjam',
            'tanggal_kembali' => null,
            'kondisi_kembali' => null,
        ]);
    }

    public function dikembalikan(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'dikembalikan',
            'tanggal_kembali' => Carbon::parse($attributes['tanggal_pinjam'])->copy()->addDays(3),
            'kondisi_kembali' => 'baik',
        ]);
    }

    public function untukPegawai(): static
    {
        return $this->state(fn () => [
            'peminjam_type' => Pegawai::class,
            'peminjam_id' => Pegawai::factory(),
        ]);
    }
}
