<?php

namespace Database\Factories;

use App\Models\Aduan;
use App\Models\Pegawai;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Aduan>
 */
class AduanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $judulList = [
            'akademik' => ['Nilai raport tidak sesuai', 'Guru jarang masuk kelas', 'Buku pelajaran tidak tersedia', 'Jadwal ujian mendadak'],
            'fasilitas' => ['Toilet sekolah rusak', 'Proyektor kelas tidak berfungsi', 'Kantin tidak bersih', 'Lapangan olahraga tidak terawat'],
            'perlakuan' => ['Bullying oleh senior', 'Guru berbicara kasar', 'Diskriminasi dalam penilaian', 'Perlakuan tidak adil dari wali kelas'],
            'keuangan' => ['Pungutan liar di kelas', 'SPP tidak tercatat', 'Dana kegiatan tidak transparan', 'Biaya LKS terlalu mahal'],
            'lainnya' => ['Jadwal piket tidak adil', 'Peraturan sekolah membingungkan', 'Informasi kelulusan terlambat', 'Kegiatan ekstrakurikuler dibatalkan'],
        ];

        $kategori = fake()->randomElement(['akademik', 'fasilitas', 'perlakuan', 'keuangan', 'lainnya']);
        $judul = fake()->randomElement($judulList[$kategori]);

        $hubunganList = ['siswa', 'ayah', 'ibu', 'wali', 'lainnya'];
        $hubungan = fake()->randomElement($hubunganList);

        $namaDepan = ['Ahmad', 'Budi', 'Citra', 'Dewi', 'Eko', 'Fitri', 'Hendra', 'Indah', 'Joko', 'Kartini'];

        return [
            'siswa_id' => Siswa::factory(),
            'pelapor' => fake()->randomElement($namaDepan).' '.fake()->lastName(),
            'hubungan_pelapor' => $hubungan,
            'kontak_pelapor' => fake()->optional(0.7)->numerify('08##########'),
            'tanggal_aduan' => fake()->dateTimeBetween('-3 months', 'now'),
            'kategori' => $kategori,
            'judul' => $judul,
            'isi' => fake()->paragraphs(2, true),
            'lampiran' => null,
            'status' => 'baru',
            'ditangani_oleh' => null,
            'tanggapan' => null,
            'tanggal_tanggapan' => null,
            'dicatat_oleh' => null,
        ];
    }

    public function diproses(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'diproses',
        ]);
    }

    public function selesai(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'selesai',
            'tanggapan' => 'Aduan telah ditindaklanjuti. '.fake()->sentence(),
            'tanggal_tanggapan' => fake()->dateTimeBetween('-1 month', 'now'),
            'ditangani_oleh' => Pegawai::factory(),
        ]);
    }
}
