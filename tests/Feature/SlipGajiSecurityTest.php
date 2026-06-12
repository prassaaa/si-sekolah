<?php

use App\Filament\Resources\SlipGajis\Pages\CreateSlipGaji;
use App\Filament\Resources\SlipGajis\Pages\EditSlipGaji;
use App\Filament\Resources\SlipGajis\Pages\ListSlipGajis;
use App\Filament\Widgets\FinancialOverview;
use App\Models\JabatanPegawai;
use App\Models\Pegawai;
use App\Models\SettingGaji;
use App\Models\SlipGaji;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

/**
 * Buat izin yang diperlukan dan kembalikan user guru (ViewAny+View saja).
 * Guru hanya boleh melihat slip milik pegawai yang terhubung ke akunnya.
 */
function buatUserGuru(): User
{
    $permissions = [
        'ViewAny:SlipGaji',
        'View:SlipGaji',
        'ViewAny:KasMasuk',
    ];

    foreach (array_merge($permissions, ['Create:SlipGaji', 'Update:SlipGaji']) as $p) {
        Permission::findOrCreate($p, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['ViewAny:SlipGaji', 'View:SlipGaji']);

    return $user;
}

/**
 * Buat user bendahara (memiliki Create:SlipGaji — pengelola payroll).
 */
function buatUserBendahara(): User
{
    $permissions = [
        'ViewAny:SlipGaji', 'View:SlipGaji', 'Create:SlipGaji',
        'Update:SlipGaji', 'Delete:SlipGaji', 'DeleteAny:SlipGaji',
        'ForceDelete:SlipGaji', 'ForceDeleteAny:SlipGaji',
        'Restore:SlipGaji', 'RestoreAny:SlipGaji',
        'Replicate:SlipGaji', 'Reorder:SlipGaji',
        'ViewAny:KasMasuk',
    ];

    foreach ($permissions as $p) {
        Permission::findOrCreate($p, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

/**
 * Buat Pegawai yang terhubung ke User tertentu lengkap dengan SettingGaji aktif.
 */
function buatPegawaiDenganSetting(User $user): array
{
    $jabatan = JabatanPegawai::factory()->create();
    $pegawai = Pegawai::factory()->create([
        'user_id' => $user->id,
        'jabatan_id' => $jabatan->id,
    ]);
    $setting = SettingGaji::factory()->create([
        'pegawai_id' => $pegawai->id,
        'is_active' => true,
    ]);

    return [$pegawai, $setting];
}

// ─── #28 Recompute server-side ──────────────────────────────────────────────

it('create: gaji_bersih tersimpan = hitungan server, bukan nilai kiriman klien', function () {
    $bendahara = buatUserBendahara();
    $this->actingAs($bendahara);

    $jabatan = JabatanPegawai::factory()->create();
    $pegawai = Pegawai::factory()->create([
        'user_id' => null,
        'jabatan_id' => $jabatan->id,
    ]);

    // SettingGaji server: gaji_bersih = 5_000_000 + 1_150_000 - 350_000 = 5_800_000
    $setting = SettingGaji::factory()->create([
        'pegawai_id' => $pegawai->id,
        'gaji_pokok' => '5000000.00',
        'tunjangan_jabatan' => '500000.00',
        'tunjangan_kehadiran' => '300000.00',
        'tunjangan_transport' => '200000.00',
        'tunjangan_makan' => '150000.00',
        'tunjangan_lainnya' => '0.00',
        'potongan_bpjs' => '250000.00',
        'potongan_pph21' => '100000.00',
        'potongan_lainnya' => '0.00',
        'is_active' => true,
    ]);

    // Klien mencoba menanamkan gaji_bersih arbitrer = 999_999_999
    Livewire::test(CreateSlipGaji::class)
        ->fillForm([
            'pegawai_id' => $pegawai->id,
            'tahun' => 2026,
            'bulan' => 1,
            'status' => 'draft',
            'gaji_bersih' => 999999999,        // nilai manipulatif dari klien
            'gaji_pokok' => 999999999,
            'total_tunjangan' => 999999999,
            'total_potongan' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $slip = SlipGaji::first();

    // Harus menggunakan nilai dari SettingGaji server
    expect((float) $slip->gaji_bersih)->toBe(5800000.0)
        ->and((float) $slip->gaji_pokok)->toBe(5000000.0)
        ->and((float) $slip->total_tunjangan)->toBe(1150000.0)
        ->and((float) $slip->total_potongan)->toBe(350000.0);
});

it('edit: gaji_bersih tersimpan = hitungan server, bukan nilai kiriman klien', function () {
    $bendahara = buatUserBendahara();
    $this->actingAs($bendahara);

    $jabatan = JabatanPegawai::factory()->create();
    $pegawai = Pegawai::factory()->create([
        'user_id' => null,
        'jabatan_id' => $jabatan->id,
    ]);

    $setting = SettingGaji::factory()->create([
        'pegawai_id' => $pegawai->id,
        'gaji_pokok' => '3000000.00',
        'tunjangan_jabatan' => '200000.00',
        'tunjangan_kehadiran' => '100000.00',
        'tunjangan_transport' => '0.00',
        'tunjangan_makan' => '0.00',
        'tunjangan_lainnya' => '0.00',
        'potongan_bpjs' => '150000.00',
        'potongan_pph21' => '50000.00',
        'potongan_lainnya' => '0.00',
        'is_active' => true,
    ]);

    $slip = SlipGaji::factory()->create([
        'pegawai_id' => $pegawai->id,
        'setting_gaji_id' => $setting->id,
        'tahun' => 2026,
        'bulan' => 1,
    ]);

    // Klien mencoba mengubah gaji_bersih menjadi nilai arbitrer
    Livewire::test(EditSlipGaji::class, ['record' => $slip->id])
        ->fillForm([
            'pegawai_id' => $pegawai->id,
            'tahun' => 2026,
            'bulan' => 1,
            'status' => 'draft',
            'gaji_bersih' => 1,       // nilai manipulatif
            'gaji_pokok' => 1,
            'total_tunjangan' => 0,
            'total_potongan' => 0,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $slip->refresh();

    // gaji_bersih server = 3_000_000 + 300_000 - 200_000 = 3_100_000
    expect((float) $slip->gaji_bersih)->toBe(3100000.0)
        ->and((float) $slip->gaji_pokok)->toBe(3000000.0);
});

// ─── #29 Scoping kepemilikan ────────────────────────────────────────────────

it('guru hanya melihat slip milik pegawai-nya di list', function () {
    $guru = buatUserGuru();
    $this->actingAs($guru);

    [$pegawaiguru, $settingGuru] = buatPegawaiDenganSetting($guru);

    // Slip milik guru
    $slipMilik = SlipGaji::factory()->create([
        'pegawai_id' => $pegawaiguru->id,
        'setting_gaji_id' => $settingGuru->id,
        'tahun' => 2026,
        'bulan' => 1,
    ]);

    // Slip milik pegawai lain (tanpa user_id terhubung ke guru)
    $jabatanLain = JabatanPegawai::factory()->create();
    $pegawaiLain = Pegawai::factory()->create([
        'user_id' => null,
        'jabatan_id' => $jabatanLain->id,
    ]);
    $settingLain = SettingGaji::factory()->create([
        'pegawai_id' => $pegawaiLain->id,
        'is_active' => true,
    ]);
    $slipLain = SlipGaji::factory()->create([
        'pegawai_id' => $pegawaiLain->id,
        'setting_gaji_id' => $settingLain->id,
        'tahun' => 2026,
        'bulan' => 2,
    ]);

    Livewire::test(ListSlipGajis::class)
        ->assertCanSeeTableRecords([$slipMilik])
        ->assertCanNotSeeTableRecords([$slipLain]);
});

it('guru tidak dapat memuat slip pegawai lain (record tidak ditemukan di scope)', function () {
    $guru = buatUserGuru();

    // Pegawai lain tanpa relasi ke user guru
    $jabatanLain = JabatanPegawai::factory()->create();
    $pegawaiLain = Pegawai::factory()->create([
        'user_id' => null,
        'jabatan_id' => $jabatanLain->id,
    ]);
    $settingLain = SettingGaji::factory()->create([
        'pegawai_id' => $pegawaiLain->id,
        'is_active' => true,
    ]);
    $slipLain = SlipGaji::factory()->create([
        'pegawai_id' => $pegawaiLain->id,
        'setting_gaji_id' => $settingLain->id,
    ]);

    $this->actingAs($guru);

    // getEloquentQuery() membatasi ke slip milik pegawai user sendiri.
    // Record milik pegawai lain tidak muncul di query guru, sehingga
    // Filament melempar ModelNotFoundException (perilaku setara 404/forbidden).
    expect(fn () => Livewire::test(EditSlipGaji::class, ['record' => $slipLain->id]))
        ->toThrow(ModelNotFoundException::class);
});

it('bendahara (punya Create:SlipGaji) melihat semua slip di list', function () {
    $bendahara = buatUserBendahara();
    $this->actingAs($bendahara);

    // Dua pegawai berbeda tanpa relasi ke user bendahara
    $jabatan = JabatanPegawai::factory()->create();
    $pegawai1 = Pegawai::factory()->create(['user_id' => null, 'jabatan_id' => $jabatan->id]);
    $pegawai2 = Pegawai::factory()->create(['user_id' => null, 'jabatan_id' => $jabatan->id]);

    $setting1 = SettingGaji::factory()->create(['pegawai_id' => $pegawai1->id, 'is_active' => true]);
    $setting2 = SettingGaji::factory()->create(['pegawai_id' => $pegawai2->id, 'is_active' => true]);

    $slip1 = SlipGaji::factory()->create([
        'pegawai_id' => $pegawai1->id,
        'setting_gaji_id' => $setting1->id,
        'tahun' => 2026,
        'bulan' => 1,
    ]);
    $slip2 = SlipGaji::factory()->create([
        'pegawai_id' => $pegawai2->id,
        'setting_gaji_id' => $setting2->id,
        'tahun' => 2026,
        'bulan' => 1,
    ]);

    Livewire::test(ListSlipGajis::class)
        ->assertCanSeeTableRecords([$slip1, $slip2]);
});

// ─── #67 Gate FinancialOverview ─────────────────────────────────────────────

it('FinancialOverview::canView() mengembalikan false untuk guru tanpa ViewAny:KasMasuk', function () {
    Permission::findOrCreate('ViewAny:SlipGaji', 'web');
    Permission::findOrCreate('View:SlipGaji', 'web');
    Permission::findOrCreate('ViewAny:KasMasuk', 'web');

    $guru = User::factory()->create();
    $guru->givePermissionTo(['ViewAny:SlipGaji', 'View:SlipGaji']);

    $this->actingAs($guru);

    expect(FinancialOverview::canView())->toBeFalse();
});

it('FinancialOverview::canView() mengembalikan true untuk user dengan ViewAny:KasMasuk', function () {
    Permission::findOrCreate('ViewAny:KasMasuk', 'web');

    $bendahara = User::factory()->create();
    $bendahara->givePermissionTo('ViewAny:KasMasuk');

    $this->actingAs($bendahara);

    expect(FinancialOverview::canView())->toBeTrue();
});
