<?php

namespace Database\Seeders;

use App\Models\Aduan;
use App\Models\Pegawai;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AduanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $siswas = Siswa::query()->whereNull('deleted_at')->inRandomOrder()->take(10)->get();

        if ($siswas->isEmpty()) {
            $this->command->warn('Tidak ada data siswa. Silakan jalankan SiswaSeeder terlebih dahulu.');

            return;
        }

        $pegawai = Pegawai::first();

        $aduanData = [
            [
                'pelapor' => 'Budi Santoso',
                'hubungan_pelapor' => 'ayah',
                'kontak_pelapor' => '081234567890',
                'kategori' => 'akademik',
                'judul' => 'Nilai raport anak saya tidak sesuai',
                'isi' => 'Saya ingin melaporkan bahwa nilai raport anak saya pada semester ini tidak sesuai dengan hasil ujian yang sebenarnya. Nilai matematika yang tertera 65, padahal anak saya yakin mendapat nilai di atas 80.',
                'status' => 'selesai',
                'tanggapan' => 'Telah dilakukan verifikasi dengan guru mata pelajaran. Nilai telah diperbaiki sesuai hasil ujian yang sebenarnya.',
                'tanggal_tanggapan' => Carbon::now()->subDays(5),
            ],
            [
                'pelapor' => 'Siti Rahayu',
                'hubungan_pelapor' => 'ibu',
                'kontak_pelapor' => '082345678901',
                'kategori' => 'fasilitas',
                'judul' => 'Toilet putri sekolah tidak layak',
                'isi' => 'Toilet putri di lantai 2 sudah rusak sejak sebulan lalu dan belum diperbaiki. Anak-anak terpaksa menggunakan toilet lantai 1 yang jauh dari kelas mereka.',
                'status' => 'diproses',
                'tanggapan' => null,
                'tanggal_tanggapan' => null,
            ],
            [
                'pelapor' => 'Eko Prasetyo',
                'hubungan_pelapor' => 'siswa',
                'kontak_pelapor' => '083456789012',
                'kategori' => 'perlakuan',
                'judul' => 'Perlakuan tidak adil dari guru',
                'isi' => 'Saya merasa diperlakukan tidak adil oleh guru dalam pembagian kelompok belajar. Saya selalu ditempatkan di kelompok yang kurang aktif sehingga nilai tugas kelompok saya selalu rendah.',
                'status' => 'baru',
                'tanggapan' => null,
                'tanggal_tanggapan' => null,
            ],
            [
                'pelapor' => 'Dewi Lestari',
                'hubungan_pelapor' => 'ibu',
                'kontak_pelapor' => '084567890123',
                'kategori' => 'keuangan',
                'judul' => 'Pungutan biaya LKS tidak resmi',
                'isi' => 'Wali kelas meminta biaya pembelian LKS sebesar Rp150.000 per mata pelajaran namun tidak ada kwitansi resmi dari sekolah. Sudah berlangsung selama 2 semester.',
                'status' => 'selesai',
                'tanggapan' => 'Telah dilakukan klarifikasi dengan wali kelas dan kepala sekolah. Biaya LKS dikembalikan dan prosedur pembayaran diperketat.',
                'tanggal_tanggapan' => Carbon::now()->subDays(10),
            ],
            [
                'pelapor' => 'Hendra Wijaya',
                'hubungan_pelapor' => 'ayah',
                'kontak_pelapor' => '085678901234',
                'kategori' => 'perlakuan',
                'judul' => 'Kasus bullying di kelas',
                'isi' => 'Anak saya sering mendapat ejekan dan intimidasi dari beberapa teman sekelasnya. Sudah berlangsung 3 minggu dan anak saya mulai tidak mau berangkat sekolah.',
                'status' => 'diproses',
                'tanggapan' => null,
                'tanggal_tanggapan' => null,
            ],
            [
                'pelapor' => 'Indah Permata',
                'hubungan_pelapor' => 'wali',
                'kontak_pelapor' => '086789012345',
                'kategori' => 'akademik',
                'judul' => 'Guru sering tidak hadir mengajar',
                'isi' => 'Guru mata pelajaran IPA kelas 9 sering tidak hadir tanpa ada guru pengganti. Sudah terjadi 5 kali dalam bulan ini sehingga materi pelajaran tertinggal jauh.',
                'status' => 'selesai',
                'tanggapan' => 'Kepala sekolah telah memanggil guru yang bersangkutan dan menjadwalkan jam pengganti untuk materi yang tertinggal.',
                'tanggal_tanggapan' => Carbon::now()->subDays(2),
            ],
            [
                'pelapor' => 'Joko Susilo',
                'hubungan_pelapor' => 'ayah',
                'kontak_pelapor' => '087890123456',
                'kategori' => 'fasilitas',
                'judul' => 'Proyektor kelas rusak tidak diganti',
                'isi' => 'Proyektor di kelas 8A sudah rusak sejak 2 bulan lalu. Pembelajaran menjadi kurang efektif karena guru tidak bisa menampilkan materi visual.',
                'status' => 'baru',
                'tanggapan' => null,
                'tanggal_tanggapan' => null,
            ],
            [
                'pelapor' => 'Kartini Wahyuni',
                'hubungan_pelapor' => 'ibu',
                'kontak_pelapor' => '088901234567',
                'kategori' => 'lainnya',
                'judul' => 'Jadwal ekstrakurikuler dibatalkan tanpa pemberitahuan',
                'isi' => 'Kegiatan ekstrakurikuler pramuka yang dijanjikan pada awal semester belum juga dilaksanakan. Anak-anak sudah membayar seragam dan perlengkapan.',
                'status' => 'ditolak',
                'tanggapan' => 'Pembatalan ekstrakurikuler disebabkan pembina yang mengundurkan diri. Sekolah sedang mencari pembina baru dan dana kegiatan akan dikembalikan.',
                'tanggal_tanggapan' => Carbon::now()->subDays(7),
            ],
            [
                'pelapor' => 'Agus Budiman',
                'hubungan_pelapor' => 'ayah',
                'kontak_pelapor' => null,
                'kategori' => 'keuangan',
                'judul' => 'SPP bulan lalu tidak tercatat',
                'isi' => 'Saya sudah membayar SPP bulan lalu namun pada tagihan bulan ini masih tercantum tunggakan bulan sebelumnya. Bukti pembayaran sudah ada.',
                'status' => 'selesai',
                'tanggapan' => 'Telah dilakukan pengecekan dengan bagian keuangan. Data pembayaran sudah diperbaiki dan kwitansi diberikan.',
                'tanggal_tanggapan' => Carbon::now()->subDays(1),
            ],
            [
                'pelapor' => 'Fitri Handayani',
                'hubungan_pelapor' => 'ibu',
                'kontak_pelapor' => '089012345678',
                'kategori' => 'akademik',
                'judul' => 'Buku pelajaran kurikulum baru belum tersedia',
                'isi' => 'Sudah 3 minggu semester berjalan namun buku pelajaran kurikulum Merdeka untuk beberapa mata pelajaran belum tersedia. Siswa hanya mengandalkan catatan dari papan tulis.',
                'status' => 'baru',
                'tanggapan' => null,
                'tanggal_tanggapan' => null,
            ],
        ];

        $siswasArray = $siswas->values();

        foreach ($aduanData as $index => $data) {
            $siswa = $siswasArray[$index % $siswasArray->count()];
            $tanggalAduan = Carbon::now()->subDays(rand(1, 60));

            Aduan::create([
                'siswa_id' => $siswa->id,
                'pelapor' => $data['pelapor'],
                'hubungan_pelapor' => $data['hubungan_pelapor'],
                'kontak_pelapor' => $data['kontak_pelapor'],
                'tanggal_aduan' => $tanggalAduan->format('Y-m-d'),
                'kategori' => $data['kategori'],
                'judul' => $data['judul'],
                'isi' => $data['isi'],
                'lampiran' => null,
                'status' => $data['status'],
                'ditangani_oleh' => in_array($data['status'], ['selesai', 'ditolak', 'diproses']) ? $pegawai?->id : null,
                'tanggapan' => $data['tanggapan'],
                'tanggal_tanggapan' => $data['tanggal_tanggapan'],
                'dicatat_oleh' => null,
            ]);
        }

        $this->command->info('AduanSeeder: 10 records seeded successfully.');
    }
}
