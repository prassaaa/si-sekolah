<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * All resources with their permission configurations.
     *
     * @var array<string, array<string>>
     */
    private array $allResources = [
        'User', 'Role', 'Activity', 'Sekolah', 'Informasi',
        'JabatanPegawai', 'Pegawai', 'TahunAjaran', 'Semester',
        'MataPelajaran', 'JamPelajaran', 'Kelas', 'Siswa',
        'JadwalPelajaran', 'Absensi', 'Tahfidz', 'IzinKeluar', 'IzinPulang',
        'Prestasi', 'Pelanggaran', 'Konseling', 'Aduan', 'KenaikanKelas', 'Kelulusan',
        'KategoriPembayaran', 'JenisPembayaran', 'TagihanSiswa', 'Pembayaran',
        'Akun', 'JurnalUmum', 'SaldoAwal', 'KasMasuk', 'KasKeluar',
        'BuktiTransfer', 'SettingGaji', 'SlipGaji', 'Pajak', 'UnitPos',
        'PosBayar', 'PembayaranPaket', 'TabunganSiswa',
        'PresensiHarian', 'KartuRfid', 'RfidDevice', 'RfidScanLog',
        'PresensiHarianPegawai',
        'SarprasKategori', 'Ruangan', 'SarprasBarang', 'SarprasPeminjaman',
        'SarprasPemeliharaan', 'SarprasPengadaan', 'SarprasPenghapusan',
    ];

    /**
     * Standard CRUD actions.
     *
     * @var array<string>
     */
    private array $allActions = [
        'ViewAny', 'View', 'Create', 'Update', 'Delete',
        'DeleteAny', 'ForceDelete', 'ForceDeleteAny',
        'Restore', 'RestoreAny', 'Replicate', 'Reorder',
    ];

    public function run(): void
    {
        $this->createPermissions();
        $this->createRoles();
    }

    /**
     * All page permission names (View:<ClassName> format used by Shield HasPageShield trait).
     *
     * @var array<string>
     */
    private array $allPages = [
        'Neraca', 'NeracaSaldo', 'LabaRugi', 'BukuBesar', 'PerubahanModal', 'ArusKasBank',
        'LaporanJurnal', 'LaporanDebitKredit', 'LaporanKeuangan',
        'LaporanPembayaran', 'LaporanPembayaranPerKelas', 'LaporanPembayaranPerTanggal',
        'LaporanTagihanSiswa', 'LaporanUnitPos', 'LaporanTabungan',
        'LaporanGaji', 'KirimNotifGaji', 'KirimTagihan', 'LaporanPenyusutan',
        'LaporanInventaris', 'LaporanKondisiSarpras', 'LaporanPemeliharaanSarpras',
        'LaporanPeminjamanSarpras',
        'LaporanSiswa', 'LaporanTahfidz', 'KirimNotifPresensi', 'MonitorGerbang',
    ];

    /**
     * Create all permissions for all resources.
     */
    private function createPermissions(): void
    {
        foreach ($this->allResources as $resource) {
            $actions = $resource === 'Activity' ? ['ViewAny', 'View'] : $this->allActions;

            if ($resource === 'RfidScanLog') {
                $actions = ['ViewAny', 'View'];
            }

            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$action}:{$resource}"]);
            }
        }

        foreach ($this->allPages as $page) {
            Permission::firstOrCreate(['name' => "View:{$page}"]);
        }
    }

    /**
     * Create all roles with their permissions.
     */
    private function createRoles(): void
    {
        // 1. Super Admin - Full access
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->syncPermissions(Permission::all());

        // 2. Bendahara - Keuangan, Akuntansi, Kas & Bank, Penggajian, Setting Pembayaran
        $bendahara = Role::firstOrCreate(['name' => 'bendahara']);
        $bendahara->syncPermissions($this->getBendaharaPermissions());

        // 3. Tata Usaha - Akademik, Kesiswaan, Kepegawaian, Pengaturan
        $tataUsaha = Role::firstOrCreate(['name' => 'tata_usaha']);
        $tataUsaha->syncPermissions($this->getTataUsahaPermissions());

        // 4. Guru BK - Konseling, Pelanggaran, Prestasi, Izin
        $guruBk = Role::firstOrCreate(['name' => 'guru_bk']);
        $guruBk->syncPermissions($this->getGuruBkPermissions());

        // 5. Guru - View jadwal, input tahfidz, view siswa
        $guru = Role::firstOrCreate(['name' => 'guru']);
        $guru->syncPermissions($this->getGuruPermissions());

        // 6. Wali Kelas - Sama dengan guru + kelola siswa kelasnya
        $waliKelas = Role::firstOrCreate(['name' => 'wali_kelas']);
        $waliKelas->syncPermissions($this->getWaliKelasPermissions());

        // 7. Petugas Piket - Kelola izin keluar/pulang
        $petugasPiket = Role::firstOrCreate(['name' => 'petugas_piket']);
        $petugasPiket->syncPermissions($this->getPetugasPiketPermissions());

        // 8. Petugas Sarpras - Kelola sarana & prasarana
        $petugasSarpras = Role::firstOrCreate(['name' => 'petugas_sarpras']);
        $petugasSarpras->syncPermissions($this->getPetugasSarprasPermissions());

        // Keep admin role for backward compatibility (same as tata_usaha)
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($this->getTataUsahaPermissions());
    }

    /**
     * Generate a page view permission (as single-element array for use with array_merge).
     *
     * @return array<string>
     */
    private function pagePermission(string $page): array
    {
        return ["View:{$page}"];
    }

    /**
     * Generate full CRUD permissions for a resource.
     *
     * @return array<string>
     */
    private function fullCrud(string $resource): array
    {
        return array_map(fn ($action) => "{$action}:{$resource}", $this->allActions);
    }

    /**
     * Generate view-only permissions for a resource.
     *
     * @return array<string>
     */
    private function viewOnly(string $resource): array
    {
        return [
            "ViewAny:{$resource}",
            "View:{$resource}",
        ];
    }

    /**
     * Generate create-only permissions (view + create) for a resource.
     *
     * @return array<string>
     */
    private function createOnly(string $resource): array
    {
        return [
            "ViewAny:{$resource}",
            "View:{$resource}",
            "Create:{$resource}",
        ];
    }

    /**
     * Generate permissions without delete for a resource.
     *
     * @return array<string>
     */
    private function noDelete(string $resource): array
    {
        return [
            "ViewAny:{$resource}",
            "View:{$resource}",
            "Create:{$resource}",
            "Update:{$resource}",
        ];
    }

    /**
     * Get Bendahara permissions.
     * Focus: Keuangan, Akuntansi, Kas & Bank, Penggajian, Setting Pembayaran
     *
     * @return array<string>
     */
    private function getBendaharaPermissions(): array
    {
        return array_merge(
            // KEUANGAN - Full CRUD
            $this->fullCrud('KategoriPembayaran'),
            $this->fullCrud('JenisPembayaran'),
            $this->fullCrud('TagihanSiswa'),
            $this->fullCrud('Pembayaran'),
            $this->fullCrud('TabunganSiswa'),
            $this->fullCrud('BuktiTransfer'),

            // AKUNTANSI - Full CRUD
            $this->fullCrud('Akun'),
            $this->fullCrud('JurnalUmum'),
            $this->fullCrud('SaldoAwal'),

            // KAS & BANK - Full CRUD
            $this->fullCrud('KasMasuk'),
            $this->fullCrud('KasKeluar'),

            // PENGGAJIAN - Full CRUD
            $this->fullCrud('SettingGaji'),
            $this->fullCrud('SlipGaji'),

            // SETTING PEMBAYARAN - Full CRUD
            $this->fullCrud('PosBayar'),
            $this->fullCrud('Pajak'),
            $this->fullCrud('UnitPos'),
            $this->fullCrud('PembayaranPaket'),

            // REFERENSI - View Only
            $this->viewOnly('Siswa'),
            $this->viewOnly('Pegawai'),
            $this->viewOnly('Kelas'),

            // HALAMAN LAPORAN KEUANGAN & AKUNTANSI
            $this->pagePermission('Neraca'),
            $this->pagePermission('NeracaSaldo'),
            $this->pagePermission('LabaRugi'),
            $this->pagePermission('BukuBesar'),
            $this->pagePermission('PerubahanModal'),
            $this->pagePermission('ArusKasBank'),
            $this->pagePermission('LaporanJurnal'),
            $this->pagePermission('LaporanDebitKredit'),
            $this->pagePermission('LaporanKeuangan'),
            $this->pagePermission('LaporanPembayaran'),
            $this->pagePermission('LaporanPembayaranPerKelas'),
            $this->pagePermission('LaporanPembayaranPerTanggal'),
            $this->pagePermission('LaporanTagihanSiswa'),
            $this->pagePermission('LaporanUnitPos'),
            $this->pagePermission('LaporanTabungan'),
            $this->pagePermission('LaporanGaji'),
            $this->pagePermission('KirimNotifGaji'),
            $this->pagePermission('KirimTagihan'),
            $this->pagePermission('LaporanPenyusutan'),
        );
    }

    /**
     * Get Tata Usaha permissions.
     * Focus: Akademik, Kesiswaan, Kepegawaian, Pengaturan
     *
     * @return array<string>
     */
    private function getTataUsahaPermissions(): array
    {
        return array_merge(
            // AKADEMIK - Full CRUD
            $this->fullCrud('TahunAjaran'),
            $this->fullCrud('Semester'),
            $this->fullCrud('MataPelajaran'),
            $this->fullCrud('JamPelajaran'),
            $this->fullCrud('JadwalPelajaran'),
            $this->fullCrud('Absensi'),
            $this->fullCrud('KenaikanKelas'),
            $this->fullCrud('Kelulusan'),

            // KESISWAAN - Full CRUD
            $this->fullCrud('Siswa'),
            $this->fullCrud('Kelas'),
            $this->fullCrud('Tahfidz'),
            $this->fullCrud('IzinKeluar'),
            $this->fullCrud('IzinPulang'),
            $this->fullCrud('Prestasi'),
            $this->fullCrud('Pelanggaran'),
            $this->fullCrud('Konseling'),
            $this->fullCrud('Aduan'),

            // KEPEGAWAIAN - Full CRUD
            $this->fullCrud('JabatanPegawai'),
            $this->fullCrud('Pegawai'),

            // PENGATURAN
            $this->noDelete('Sekolah'), // Can't delete school settings
            $this->fullCrud('Informasi'),

            // PRESENSI HARIAN & RFID
            $this->fullCrud('PresensiHarian'),
            $this->fullCrud('PresensiHarianPegawai'),
            $this->fullCrud('KartuRfid'),
            $this->fullCrud('RfidDevice'),
            $this->viewOnly('RfidScanLog'),

            // HALAMAN LAPORAN KESISWAAN & NOTIFIKASI
            $this->pagePermission('LaporanSiswa'),
            $this->pagePermission('LaporanTahfidz'),
            $this->pagePermission('KirimNotifPresensi'),
            $this->pagePermission('MonitorGerbang'),
            $this->pagePermission('LaporanPembayaran'),
            $this->pagePermission('LaporanTagihanSiswa'),
        );
    }

    /**
     * Get Guru BK permissions.
     * Focus: Konseling, Pelanggaran, Prestasi, Izin
     *
     * @return array<string>
     */
    private function getGuruBkPermissions(): array
    {
        return array_merge(
            // Full CRUD
            $this->fullCrud('Konseling'),
            $this->fullCrud('Pelanggaran'),
            $this->fullCrud('Aduan'),
            $this->fullCrud('Prestasi'),

            // No Delete for Izin
            $this->noDelete('IzinKeluar'),
            $this->noDelete('IzinPulang'),

            // View Only - Referensi
            $this->viewOnly('Siswa'),
            $this->viewOnly('Kelas'),

            // PRESENSI HARIAN - View only (cek pola alpha)
            $this->viewOnly('PresensiHarian'),

            // HALAMAN LAPORAN
            $this->pagePermission('LaporanSiswa'),
            $this->pagePermission('LaporanTahfidz'),
        );
    }

    /**
     * Get Guru permissions.
     * Focus: View jadwal, input tahfidz, view siswa
     *
     * @return array<string>
     */
    private function getGuruPermissions(): array
    {
        return array_merge(
            // View Only
            $this->viewOnly('JadwalPelajaran'),
            $this->viewOnly('MataPelajaran'),
            $this->viewOnly('Siswa'),
            $this->viewOnly('Kelas'),
            $this->viewOnly('Pegawai'), // Self view
            $this->viewOnly('SlipGaji'), // Self view

            // Tahfidz - Create & Update, no delete
            $this->noDelete('Tahfidz'),

            // Absensi - Create & Update, no delete
            $this->noDelete('Absensi'),

            // Prestasi & Pelanggaran - Create only
            $this->createOnly('Prestasi'),
            $this->createOnly('Pelanggaran'),
        );
    }

    /**
     * Get Wali Kelas permissions.
     * Focus: Same as Guru + manage students in their class
     *
     * @return array<string>
     */
    private function getWaliKelasPermissions(): array
    {
        return array_merge(
            // All Guru permissions
            $this->getGuruPermissions(),

            // Additional for Wali Kelas
            ['Update:Siswa'], // Can update students in their class

            // KenaikanKelas - Create only
            $this->createOnly('KenaikanKelas'),

            // Kelulusan - View only
            $this->viewOnly('Kelulusan'),

            // Konseling - View only
            $this->viewOnly('Konseling'),

            // PRESENSI HARIAN - View only (pantau anak walinya)
            $this->viewOnly('PresensiHarian'),
        );
    }

    /**
     * Get Petugas Piket permissions.
     * Focus: Izin Keluar/Pulang management
     *
     * @return array<string>
     */
    private function getPetugasPiketPermissions(): array
    {
        return array_merge(
            // No Delete for Izin
            $this->noDelete('IzinKeluar'),
            $this->noDelete('IzinPulang'),

            // View Only - Referensi
            $this->viewOnly('Siswa'),
            $this->viewOnly('Kelas'),

            // PRESENSI HARIAN - Override manual + monitoring
            $this->noDelete('PresensiHarian'),
            $this->viewOnly('KartuRfid'),
            $this->viewOnly('RfidScanLog'),

            // HALAMAN
            $this->pagePermission('MonitorGerbang'),
        );
    }

    /**
     * Get Petugas Sarpras permissions.
     * Focus: Sarana & Prasarana (kategori, ruangan, barang, peminjaman, pemeliharaan, pengadaan, penghapusan).
     *
     * @return array<string>
     */
    private function getPetugasSarprasPermissions(): array
    {
        return array_merge(
            $this->fullCrud('SarprasKategori'),
            $this->fullCrud('Ruangan'),
            $this->fullCrud('SarprasBarang'),
            $this->fullCrud('SarprasPeminjaman'),
            $this->fullCrud('SarprasPemeliharaan'),
            $this->fullCrud('SarprasPengadaan'),
            $this->fullCrud('SarprasPenghapusan'),
            // Konteks read-only untuk relasi peminjam
            $this->viewOnly('Siswa'),
            $this->viewOnly('Pegawai'),

            // HALAMAN LAPORAN SARPRAS
            $this->pagePermission('LaporanInventaris'),
            $this->pagePermission('LaporanKondisiSarpras'),
            $this->pagePermission('LaporanPemeliharaanSarpras'),
            $this->pagePermission('LaporanPeminjamanSarpras'),
            $this->pagePermission('LaporanPenyusutan'),
        );
    }
}
