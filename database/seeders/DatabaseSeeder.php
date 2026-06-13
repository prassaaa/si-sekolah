<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Strategi event (temuan audit #3):
     *
     * - Seeder LEGACY/PRA-PEMBUKUAN dijalankan dengan model event DIMATIKAN
     *   ($legacySeeders, dibungkus Model::withoutEvents). Mereka menyetel kolom
     *   turunan secara manual (saldo tabungan, total_terbayar tagihan, nomor
     *   sarpras, stok) dan bertanggal SEBELUM cut-off, jadi memang TIDAK boleh
     *   memicu poster/observer (akan menimpa kolom, melempar guard, atau
     *   memposting jurnal pra-pembukuan). Ini mempertahankan perilaku sebelum
     *   trait WithoutModelEvents kelas dihapus.
     *
     * - Jurnal keuangan demo yang JUJUR dibuat di $liveSeeders dengan event
     *   AKTIF: JurnalUmumSeeder (jurnal manual operasional, tanpa memalsukan
     *   SPP/gaji) dan DemoKeuanganPascaCutoffSeeder (Pembayaran/SlipGaji/
     *   Tabungan/Kas bertanggal >= cut-off yang benar-benar diposting oleh
     *   poster produksi). Tidak ada lagi jurnal SPP/gaji palsu.
     */
    public function run(): void
    {
        Model::withoutEvents(fn () => $this->call($this->legacySeeders()));

        $this->call($this->liveSeeders());
    }

    /**
     * Seeder yang harus berjalan dengan event dimatikan (data pra-pembukuan /
     * kolom turunan disetel manual).
     *
     * @return list<class-string<Seeder>>
     */
    private function legacySeeders(): array
    {
        return [
            // Core
            RoleSeeder::class,
            UserSeeder::class,
            SekolahSeeder::class,
            InformasiSeeder::class,

            // Kepegawaian
            JabatanPegawaiSeeder::class,

            // Akademik - Master Data
            TahunAjaranSeeder::class,
            SemesterSeeder::class,
            MataPelajaranSeeder::class,
            JamPelajaranSeeder::class,

            // Kepegawaian - Data Pegawai (setelah Jabatan)
            PegawaiSeeder::class,

            // Akademik - Kelas & Jadwal
            KelasSeeder::class,
            JadwalPelajaranSeeder::class,

            // Kesiswaan - Data Siswa
            SiswaSeeder::class,
            AbsensiSeeder::class,
            TahfidzSeeder::class,
            IzinKeluarSeeder::class,
            IzinPulangSeeder::class,
            PrestasiSeeder::class,
            PelanggaranSeeder::class,
            KonselingSeeder::class,
            AduanSeeder::class,
            KenaikanKelasSeeder::class,
            KelulusanSeeder::class,

            // Presensi Harian & RFID
            RfidDeviceSeeder::class,
            KartuRfidSeeder::class,
            PresensiHarianSeeder::class,
            PresensiHarianPegawaiSeeder::class,

            // Keuangan - Master Data
            AkunSeeder::class,
            KategoriPembayaranSeeder::class,
            JenisPembayaranSeeder::class,
            PosBayarSeeder::class,
            PembayaranPaketSeeder::class,
            PajakSeeder::class,
            UnitPosSeeder::class,
            SettingGajiSeeder::class,

            // Keuangan - Transaksi (pra cut-off; kolom turunan disetel manual)
            TagihanSiswaSeeder::class,
            PembayaranSeeder::class,
            TabunganSiswaSeeder::class,
            SaldoAwalSeeder::class,
            KasMasukSeeder::class,
            KasKeluarSeeder::class,
            SlipGajiSeeder::class,
            BuktiTransferSeeder::class,
        ];
    }

    /**
     * Seeder yang berjalan dengan event AKTIF agar nomor/observer/poster nyata
     * berjalan.
     *
     * Catatan Sarpras: seeder transaksi sarpras MENGANDALKAN hook model untuk
     * generate `nomor` (PJM/dst.), jadi WAJIB event aktif. Posternya ber-gate
     * cut-off sehingga transaksi sarpras pra cut-off tidak ikut menjurnal.
     *
     * @return list<class-string<Seeder>>
     */
    private function liveSeeders(): array
    {
        return [
            // Sarpras - Master Data (dependensi awal)
            SarprasKategoriSeeder::class,
            RuanganSeeder::class,

            // Sarpras - Inventaris (bergantung pada kategori + ruangan)
            SarprasBarangSeeder::class,

            // Sarpras - Transaksi (butuh event untuk nomor; poster ber-gate cut-off)
            SarprasPeminjamanSeeder::class,
            SarprasPemeliharaanSeeder::class,
            SarprasPengadaanSeeder::class,
            SarprasPenghapusanSeeder::class,

            // Jurnal manual operasional (tanpa memalsukan SPP/gaji).
            JurnalUmumSeeder::class,

            // Demo keuangan pasca cut-off: jurnal SPP/gaji/tabungan/kas JUJUR
            // yang benar-benar terbentuk lewat poster nyata.
            DemoKeuanganPascaCutoffSeeder::class,
        ];
    }
}
