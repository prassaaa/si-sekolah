<?php

namespace Database\Seeders;

use App\Models\Pegawai;
use App\Models\SarprasBarang;
use App\Models\SarprasPeminjaman;
use App\Models\Siswa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SarprasPeminjamanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $barangs = SarprasBarang::where('status', 'tersedia')
            ->where('tipe', 'aset')
            ->where('kondisi', 'baik')
            ->take(5)
            ->get();

        if ($barangs->isEmpty()) {
            $this->command->warn('Tidak ada barang tersedia. Jalankan SarprasBarangSeeder terlebih dahulu.');

            return;
        }

        $siswas = Siswa::take(3)->get();
        $pegawais = Pegawai::take(3)->get();
        $petugas = Pegawai::first();

        if ($siswas->isEmpty() || ! $petugas) {
            $this->command->warn('Data siswa/pegawai belum ada. Jalankan SiswaSeeder dan PegawaiSeeder terlebih dahulu.');

            return;
        }

        $created = 0;

        // Peminjaman aktif oleh siswa (sudah dikembalikan)
        if ($barangs->get(0) && $siswas->get(0)) {
            $tglPinjam = Carbon::now()->subDays(14);
            SarprasPeminjaman::firstOrCreate(
                [
                    'sarpras_barang_id' => $barangs->get(0)->id,
                    'peminjam_type' => Siswa::class,
                    'peminjam_id' => $siswas->get(0)->id,
                    'tanggal_pinjam' => $tglPinjam->toDateString(),
                ],
                [
                    'jumlah' => 1,
                    'tanggal_harus_kembali' => $tglPinjam->copy()->addDays(7)->toDateString(),
                    'tanggal_kembali' => $tglPinjam->copy()->addDays(5)->toDateString(),
                    'kondisi_pinjam' => 'baik',
                    'kondisi_kembali' => 'baik',
                    'status' => 'dikembalikan',
                    'petugas_id' => $petugas->id,
                    'catatan' => 'Dipinjam untuk kegiatan belajar mengajar di kelas.',
                ]
            );
            $created++;
        }

        // Peminjaman aktif oleh siswa (masih dipinjam)
        if ($barangs->get(1) && $siswas->get(1)) {
            $tglPinjam = Carbon::now()->subDays(3);
            SarprasPeminjaman::firstOrCreate(
                [
                    'sarpras_barang_id' => $barangs->get(1)->id,
                    'peminjam_type' => Siswa::class,
                    'peminjam_id' => $siswas->get(1)->id,
                    'tanggal_pinjam' => $tglPinjam->toDateString(),
                ],
                [
                    'jumlah' => 1,
                    'tanggal_harus_kembali' => $tglPinjam->copy()->addDays(7)->toDateString(),
                    'tanggal_kembali' => null,
                    'kondisi_pinjam' => 'baik',
                    'kondisi_kembali' => null,
                    'status' => 'dipinjam',
                    'petugas_id' => $petugas->id,
                    'catatan' => 'Peminjaman untuk presentasi proyek.',
                ]
            );
            $created++;
        }

        // Peminjaman terlambat oleh siswa
        if ($barangs->get(2) && $siswas->get(2)) {
            $tglPinjam = Carbon::now()->subDays(20);
            SarprasPeminjaman::firstOrCreate(
                [
                    'sarpras_barang_id' => $barangs->get(2)->id,
                    'peminjam_type' => Siswa::class,
                    'peminjam_id' => $siswas->get(2)->id,
                    'tanggal_pinjam' => $tglPinjam->toDateString(),
                ],
                [
                    'jumlah' => 1,
                    'tanggal_harus_kembali' => $tglPinjam->copy()->addDays(7)->toDateString(),
                    'tanggal_kembali' => null,
                    'kondisi_pinjam' => 'baik',
                    'kondisi_kembali' => null,
                    'status' => 'terlambat',
                    'petugas_id' => $petugas->id,
                    'catatan' => 'Belum dikembalikan melebihi batas waktu.',
                ]
            );
            $created++;
        }

        // Peminjaman oleh pegawai (dikembalikan)
        if ($barangs->get(3) && $pegawais->get(0)) {
            $tglPinjam = Carbon::now()->subDays(30);
            SarprasPeminjaman::firstOrCreate(
                [
                    'sarpras_barang_id' => $barangs->get(3)->id,
                    'peminjam_type' => Pegawai::class,
                    'peminjam_id' => $pegawais->get(0)->id,
                    'tanggal_pinjam' => $tglPinjam->toDateString(),
                ],
                [
                    'jumlah' => 1,
                    'tanggal_harus_kembali' => $tglPinjam->copy()->addDays(3)->toDateString(),
                    'tanggal_kembali' => $tglPinjam->copy()->addDays(2)->toDateString(),
                    'kondisi_pinjam' => 'baik',
                    'kondisi_kembali' => 'baik',
                    'status' => 'dikembalikan',
                    'petugas_id' => $petugas->id,
                    'catatan' => 'Dipinjam untuk rapat dinas.',
                ]
            );
            $created++;
        }

        // Peminjaman oleh pegawai (masih dipinjam)
        if ($barangs->get(4) && $pegawais->get(1)) {
            $tglPinjam = Carbon::now()->subDays(1);
            SarprasPeminjaman::firstOrCreate(
                [
                    'sarpras_barang_id' => $barangs->get(4)->id,
                    'peminjam_type' => Pegawai::class,
                    'peminjam_id' => $pegawais->get(1)->id,
                    'tanggal_pinjam' => $tglPinjam->toDateString(),
                ],
                [
                    'jumlah' => 1,
                    'tanggal_harus_kembali' => $tglPinjam->copy()->addDays(3)->toDateString(),
                    'tanggal_kembali' => null,
                    'kondisi_pinjam' => 'baik',
                    'kondisi_kembali' => null,
                    'status' => 'dipinjam',
                    'petugas_id' => $petugas->id,
                    'catatan' => 'Dipakai untuk keperluan dokumentasi sekolah.',
                ]
            );
            $created++;
        }

        $this->command->info("SarprasPeminjaman seeded: {$created} record.");
    }
}
