<?php

namespace Database\Seeders;

use App\Models\Akun;
use Illuminate\Database\Seeder;

class AkunSeeder extends Seeder
{
    public function run(): void
    {
        $akuns = [
            // Aset
            ['kode' => '1-1001', 'nama' => 'Kas', 'tipe' => 'aset', 'posisi_normal' => 'debit', 'is_active' => true],
            ['kode' => '1-1002', 'nama' => 'Bank BCA', 'tipe' => 'aset', 'posisi_normal' => 'debit', 'is_active' => true],
            ['kode' => '1-1003', 'nama' => 'Bank Mandiri', 'tipe' => 'aset', 'posisi_normal' => 'debit', 'is_active' => true],
            ['kode' => '1-1004', 'nama' => 'Bank BSI', 'tipe' => 'aset', 'posisi_normal' => 'debit', 'is_active' => true],
            ['kode' => '1-2001', 'nama' => 'Piutang SPP', 'tipe' => 'aset', 'posisi_normal' => 'debit', 'is_active' => true],
            ['kode' => '1-3001', 'nama' => 'Perlengkapan', 'tipe' => 'aset', 'posisi_normal' => 'debit', 'is_active' => true],
            ['kode' => '1-4001', 'nama' => 'Peralatan', 'tipe' => 'aset', 'posisi_normal' => 'debit', 'is_active' => true],
            ['kode' => '1-4002', 'nama' => 'Akumulasi Penyusutan Peralatan', 'tipe' => 'aset', 'posisi_normal' => 'kredit', 'is_active' => true],

            // Liabilitas
            ['kode' => '2-1001', 'nama' => 'Hutang Usaha', 'tipe' => 'liabilitas', 'posisi_normal' => 'kredit', 'is_active' => true],
            ['kode' => '2-1002', 'nama' => 'Hutang Gaji', 'tipe' => 'liabilitas', 'posisi_normal' => 'kredit', 'is_active' => true],
            ['kode' => '2-1003', 'nama' => 'Hutang Pajak', 'tipe' => 'liabilitas', 'posisi_normal' => 'kredit', 'is_active' => true],

            // Ekuitas
            ['kode' => '3-1001', 'nama' => 'Modal Yayasan', 'tipe' => 'ekuitas', 'posisi_normal' => 'kredit', 'is_active' => true],
            ['kode' => '3-2001', 'nama' => 'Laba Ditahan', 'tipe' => 'ekuitas', 'posisi_normal' => 'kredit', 'is_active' => true],
            ['kode' => '3-3001', 'nama' => 'Prive', 'tipe' => 'ekuitas', 'posisi_normal' => 'debit', 'is_active' => true],

            // Pendapatan
            ['kode' => '4-1001', 'nama' => 'Pendapatan SPP', 'tipe' => 'pendapatan', 'posisi_normal' => 'kredit', 'is_active' => true],
            ['kode' => '4-1002', 'nama' => 'Pendapatan Uang Gedung', 'tipe' => 'pendapatan', 'posisi_normal' => 'kredit', 'is_active' => true],
            ['kode' => '4-1003', 'nama' => 'Pendapatan Seragam', 'tipe' => 'pendapatan', 'posisi_normal' => 'kredit', 'is_active' => true],
            ['kode' => '4-1004', 'nama' => 'Pendapatan Kegiatan', 'tipe' => 'pendapatan', 'posisi_normal' => 'kredit', 'is_active' => true],
            ['kode' => '4-1005', 'nama' => 'Pendapatan Lain-lain', 'tipe' => 'pendapatan', 'posisi_normal' => 'kredit', 'is_active' => true],

            // Beban
            ['kode' => '5-1001', 'nama' => 'Beban Gaji Guru', 'tipe' => 'beban', 'posisi_normal' => 'debit', 'is_active' => true],
            ['kode' => '5-1002', 'nama' => 'Beban Gaji Karyawan', 'tipe' => 'beban', 'posisi_normal' => 'debit', 'is_active' => true],
            ['kode' => '5-2001', 'nama' => 'Beban Listrik', 'tipe' => 'beban', 'posisi_normal' => 'debit', 'is_active' => true],
            ['kode' => '5-2002', 'nama' => 'Beban Air', 'tipe' => 'beban', 'posisi_normal' => 'debit', 'is_active' => true],
            ['kode' => '5-2003', 'nama' => 'Beban Telepon/Internet', 'tipe' => 'beban', 'posisi_normal' => 'debit', 'is_active' => true],
            ['kode' => '5-3001', 'nama' => 'Beban ATK', 'tipe' => 'beban', 'posisi_normal' => 'debit', 'is_active' => true],
            ['kode' => '5-3002', 'nama' => 'Beban Kebersihan', 'tipe' => 'beban', 'posisi_normal' => 'debit', 'is_active' => true],
            ['kode' => '5-4001', 'nama' => 'Beban Penyusutan', 'tipe' => 'beban', 'posisi_normal' => 'debit', 'is_active' => true],
            ['kode' => '5-5001', 'nama' => 'Beban Lain-lain', 'tipe' => 'beban', 'posisi_normal' => 'debit', 'is_active' => true],
        ];

        foreach ($akuns as $akun) {
            Akun::firstOrCreate(['kode' => $akun['kode']], $akun);
        }

        $this->command->info('Akun seeded successfully: '.count($akuns).' records');
    }
}
