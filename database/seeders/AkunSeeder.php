<?php

namespace Database\Seeders;

use App\Models\Akun;
use Illuminate\Database\Seeder;

class AkunSeeder extends Seeder
{
    public function run(): void
    {
        $akuns = [
            // Aset - Level 1 (Header)
            ['kode' => '1-0000', 'nama' => 'Aset', 'tipe' => 'aset', 'kategori' => 'header', 'parent_id' => null, 'level' => 1, 'posisi_normal' => 'debit', 'deskripsi' => 'Akun induk untuk semua aset', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],

            // Aset Lancar - Level 2
            ['kode' => '1-1000', 'nama' => 'Aset Lancar', 'tipe' => 'aset', 'kategori' => 'header', 'parent_id' => null, 'level' => 2, 'posisi_normal' => 'debit', 'deskripsi' => 'Aset yang dapat dicairkan dalam waktu 1 tahun', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],
            ['kode' => '1-1001', 'nama' => 'Kas', 'tipe' => 'aset', 'kategori' => 'kas', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'debit', 'deskripsi' => 'Kas tunai sekolah', 'saldo_awal' => 50000000, 'saldo_akhir' => 50000000, 'is_active' => true],
            ['kode' => '1-1002', 'nama' => 'Bank BCA', 'tipe' => 'aset', 'kategori' => 'bank', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'debit', 'deskripsi' => 'Rekening Bank BCA', 'saldo_awal' => 100000000, 'saldo_akhir' => 100000000, 'is_active' => true],
            ['kode' => '1-1003', 'nama' => 'Bank Mandiri', 'tipe' => 'aset', 'kategori' => 'bank', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'debit', 'deskripsi' => 'Rekening Bank Mandiri', 'saldo_awal' => 75000000, 'saldo_akhir' => 75000000, 'is_active' => true],
            ['kode' => '1-1004', 'nama' => 'Bank BSI', 'tipe' => 'aset', 'kategori' => 'bank', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'debit', 'deskripsi' => 'Rekening Bank Syariah Indonesia', 'saldo_awal' => 25000000, 'saldo_akhir' => 25000000, 'is_active' => true],
            ['kode' => '1-2001', 'nama' => 'Piutang SPP', 'tipe' => 'aset', 'kategori' => 'piutang', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'debit', 'deskripsi' => 'Piutang dari tagihan SPP siswa', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],
            ['kode' => '1-3001', 'nama' => 'Perlengkapan', 'tipe' => 'aset', 'kategori' => 'perlengkapan', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'debit', 'deskripsi' => 'Perlengkapan kantor dan sekolah', 'saldo_awal' => 5000000, 'saldo_akhir' => 5000000, 'is_active' => true],

            // Aset Tetap - Level 2
            ['kode' => '1-4000', 'nama' => 'Aset Tetap', 'tipe' => 'aset', 'kategori' => 'header', 'parent_id' => null, 'level' => 2, 'posisi_normal' => 'debit', 'deskripsi' => 'Aset tetap berwujud', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],
            ['kode' => '1-4001', 'nama' => 'Peralatan', 'tipe' => 'aset', 'kategori' => 'aset_tetap', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'debit', 'deskripsi' => 'Peralatan sekolah (komputer, proyektor, dll)', 'saldo_awal' => 50000000, 'saldo_akhir' => 50000000, 'is_active' => true],
            ['kode' => '1-4002', 'nama' => 'Akumulasi Penyusutan Peralatan', 'tipe' => 'aset', 'kategori' => 'akumulasi_penyusutan', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'kredit', 'deskripsi' => 'Akumulasi penyusutan peralatan', 'saldo_awal' => 10000000, 'saldo_akhir' => 10000000, 'is_active' => true],

            // Liabilitas - Level 1 (Header)
            ['kode' => '2-0000', 'nama' => 'Liabilitas', 'tipe' => 'liabilitas', 'kategori' => 'header', 'parent_id' => null, 'level' => 1, 'posisi_normal' => 'kredit', 'deskripsi' => 'Akun induk untuk semua kewajiban', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],
            ['kode' => '2-1001', 'nama' => 'Hutang Usaha', 'tipe' => 'liabilitas', 'kategori' => 'hutang', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'kredit', 'deskripsi' => 'Hutang kepada supplier', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],
            ['kode' => '2-1002', 'nama' => 'Hutang Gaji', 'tipe' => 'liabilitas', 'kategori' => 'hutang', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'kredit', 'deskripsi' => 'Hutang gaji pegawai', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],
            ['kode' => '2-1003', 'nama' => 'Hutang Pajak', 'tipe' => 'liabilitas', 'kategori' => 'hutang', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'kredit', 'deskripsi' => 'Hutang pajak (PPh, PPN)', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],

            // Ekuitas - Level 1 (Header)
            ['kode' => '3-0000', 'nama' => 'Ekuitas', 'tipe' => 'ekuitas', 'kategori' => 'header', 'parent_id' => null, 'level' => 1, 'posisi_normal' => 'kredit', 'deskripsi' => 'Akun induk untuk ekuitas', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],
            ['kode' => '3-1001', 'nama' => 'Modal Yayasan', 'tipe' => 'ekuitas', 'kategori' => 'modal', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'kredit', 'deskripsi' => 'Modal dari yayasan', 'saldo_awal' => 250000000, 'saldo_akhir' => 250000000, 'is_active' => true],
            ['kode' => '3-2001', 'nama' => 'Laba Ditahan', 'tipe' => 'ekuitas', 'kategori' => 'laba_ditahan', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'kredit', 'deskripsi' => 'Laba yang ditahan dari periode sebelumnya', 'saldo_awal' => 45000000, 'saldo_akhir' => 45000000, 'is_active' => true],
            ['kode' => '3-3001', 'nama' => 'Prive', 'tipe' => 'ekuitas', 'kategori' => 'prive', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'debit', 'deskripsi' => 'Pengambilan oleh pemilik/yayasan', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],

            // Pendapatan - Level 1 (Header)
            ['kode' => '4-0000', 'nama' => 'Pendapatan', 'tipe' => 'pendapatan', 'kategori' => 'header', 'parent_id' => null, 'level' => 1, 'posisi_normal' => 'kredit', 'deskripsi' => 'Akun induk untuk semua pendapatan', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],
            ['kode' => '4-1001', 'nama' => 'Pendapatan SPP', 'tipe' => 'pendapatan', 'kategori' => 'pendapatan_operasional', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'kredit', 'deskripsi' => 'Pendapatan dari pembayaran SPP', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],
            ['kode' => '4-1002', 'nama' => 'Pendapatan Uang Gedung', 'tipe' => 'pendapatan', 'kategori' => 'pendapatan_operasional', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'kredit', 'deskripsi' => 'Pendapatan dari uang gedung siswa baru', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],
            ['kode' => '4-1003', 'nama' => 'Pendapatan Seragam', 'tipe' => 'pendapatan', 'kategori' => 'pendapatan_operasional', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'kredit', 'deskripsi' => 'Pendapatan dari penjualan seragam', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],
            ['kode' => '4-1004', 'nama' => 'Pendapatan Kegiatan', 'tipe' => 'pendapatan', 'kategori' => 'pendapatan_operasional', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'kredit', 'deskripsi' => 'Pendapatan dari kegiatan sekolah', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],
            ['kode' => '4-1005', 'nama' => 'Pendapatan Lain-lain', 'tipe' => 'pendapatan', 'kategori' => 'pendapatan_lainnya', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'kredit', 'deskripsi' => 'Pendapatan lain-lain', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],

            // Beban - Level 1 (Header)
            ['kode' => '5-0000', 'nama' => 'Beban', 'tipe' => 'beban', 'kategori' => 'header', 'parent_id' => null, 'level' => 1, 'posisi_normal' => 'debit', 'deskripsi' => 'Akun induk untuk semua beban', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],
            ['kode' => '5-1001', 'nama' => 'Beban Gaji Guru', 'tipe' => 'beban', 'kategori' => 'beban_pegawai', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'debit', 'deskripsi' => 'Beban gaji guru', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],
            ['kode' => '5-1002', 'nama' => 'Beban Gaji Karyawan', 'tipe' => 'beban', 'kategori' => 'beban_pegawai', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'debit', 'deskripsi' => 'Beban gaji karyawan non-guru', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],
            ['kode' => '5-2001', 'nama' => 'Beban Listrik', 'tipe' => 'beban', 'kategori' => 'beban_utilitas', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'debit', 'deskripsi' => 'Beban tagihan listrik', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],
            ['kode' => '5-2002', 'nama' => 'Beban Air', 'tipe' => 'beban', 'kategori' => 'beban_utilitas', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'debit', 'deskripsi' => 'Beban tagihan air PDAM', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],
            ['kode' => '5-2003', 'nama' => 'Beban Telepon/Internet', 'tipe' => 'beban', 'kategori' => 'beban_utilitas', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'debit', 'deskripsi' => 'Beban tagihan telepon dan internet', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],
            ['kode' => '5-3001', 'nama' => 'Beban ATK', 'tipe' => 'beban', 'kategori' => 'beban_operasional', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'debit', 'deskripsi' => 'Beban alat tulis kantor', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],
            ['kode' => '5-3002', 'nama' => 'Beban Kebersihan', 'tipe' => 'beban', 'kategori' => 'beban_operasional', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'debit', 'deskripsi' => 'Beban perlengkapan kebersihan', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],
            ['kode' => '5-4001', 'nama' => 'Beban Penyusutan', 'tipe' => 'beban', 'kategori' => 'beban_penyusutan', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'debit', 'deskripsi' => 'Beban penyusutan aset tetap', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],
            ['kode' => '5-5001', 'nama' => 'Beban Lain-lain', 'tipe' => 'beban', 'kategori' => 'beban_lainnya', 'parent_id' => null, 'level' => 3, 'posisi_normal' => 'debit', 'deskripsi' => 'Beban lain-lain', 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true],
        ];

        foreach ($akuns as $akun) {
            Akun::firstOrCreate(['kode' => $akun['kode']], $akun);
        }

        $this->command->info('Akun seeded successfully: '.count($akuns).' records');
    }
}
