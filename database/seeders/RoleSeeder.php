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
        'Prestasi', 'Pelanggaran', 'Konseling', 'KenaikanKelas', 'Kelulusan',
        'KategoriPembayaran', 'JenisPembayaran', 'TagihanSiswa', 'Pembayaran',
        'Akun', 'JurnalUmum', 'SaldoAwal', 'KasMasuk', 'KasKeluar',
        'BuktiTransfer', 'SettingGaji', 'SlipGaji', 'Pajak', 'UnitPos',
        'PosBayar', 'PembayaranPaket', 'TabunganSiswa',
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
     * Create all permissions for all resources.
     */
    private function createPermissions(): void
    {
        foreach ($this->allResources as $resource) {
            $actions = $resource === 'Activity' ? ['ViewAny', 'View'] : $this->allActions;

            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$action}:{$resource}"]);
            }
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

        // Keep admin role for backward compatibility (same as tata_usaha)
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($this->getTataUsahaPermissions());
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

            // KEPEGAWAIAN - Full CRUD
            $this->fullCrud('JabatanPegawai'),
            $this->fullCrud('Pegawai'),

            // PENGATURAN
            $this->noDelete('Sekolah'), // Can't delete school settings
            $this->fullCrud('Informasi'),
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
            $this->fullCrud('Prestasi'),

            // No Delete for Izin
            $this->noDelete('IzinKeluar'),
            $this->noDelete('IzinPulang'),

            // View Only - Referensi
            $this->viewOnly('Siswa'),
            $this->viewOnly('Kelas'),
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
        );
    }
}
