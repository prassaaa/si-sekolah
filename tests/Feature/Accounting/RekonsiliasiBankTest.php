<?php

use App\Filament\Pages\RekonsiliasiBank;
use App\Filament\Resources\MutasiBanks\Pages\CreateMutasiBank;
use App\Filament\Resources\MutasiBanks\Pages\ListMutasiBanks;
use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\MutasiBank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

/**
 * Beri user akses ke halaman RekonsiliasiBank (pola Wave 0: permission manual via
 * Permission::firstOrCreate, TIDAK menyentuh RoleSeeder).
 */
function userRekonsiliasi(): User
{
    Permission::findOrCreate('View:RekonsiliasiBank', 'web');

    $user = User::factory()->create();
    $user->givePermissionTo('View:RekonsiliasiBank');
    test()->actingAs($user);

    return $user;
}

/**
 * Beri user akses penuh resource MutasiBank.
 */
function userMutasiBank(): User
{
    $permissions = [
        'ViewAny:MutasiBank', 'View:MutasiBank', 'Create:MutasiBank',
        'Update:MutasiBank', 'Delete:MutasiBank', 'DeleteAny:MutasiBank',
        'ForceDelete:MutasiBank', 'ForceDeleteAny:MutasiBank',
        'Restore:MutasiBank', 'RestoreAny:MutasiBank',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);
    test()->actingAs($user);

    return $user;
}

/**
 * Akun bank BCA (1-1002) — akun yang direkonsiliasi.
 */
function akunBankBca(): Akun
{
    return Akun::factory()->create([
        'kode' => '1-1002',
        'nama' => 'Bank BCA',
        'tipe' => 'aset',
        'kategori' => 'lancar',
        'posisi_normal' => 'debit',
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Akses dan otorisasi
// ─────────────────────────────────────────────────────────────────────────────

it('bendahara dengan izin View:RekonsiliasiBank dapat mengakses halaman', function () {
    userRekonsiliasi();

    Livewire::test(RekonsiliasiBank::class)->assertSuccessful();
});

it('user tanpa izin View:RekonsiliasiBank ditolak akses halaman', function () {
    $user = User::factory()->create();
    test()->actingAs($user);

    Livewire::test(RekonsiliasiBank::class)->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// Input mutasi via resource
// ─────────────────────────────────────────────────────────────────────────────

it('dapat input mutasi rekening koran via resource MutasiBank', function () {
    userMutasiBank();
    $bank = akunBankBca();

    Livewire::test(CreateMutasiBank::class)
        ->fillForm([
            'akun_id' => $bank->id,
            'tanggal' => '2026-07-05',
            'keterangan' => 'Transfer masuk SPP',
            'debit' => 1_500_000,
            'kredit' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('mutasi_banks', [
        'akun_id' => $bank->id,
        'debit' => 1_500_000,
        'is_matched' => false,
    ]);
});

it('import mutasi membuat beberapa baris sekaligus dari teks tempel', function () {
    userMutasiBank();
    $bank = akunBankBca();

    $teks = "2026-07-01;Setoran tunai;1000000;0\n2026-07-02;Biaya admin;0;15000";

    Livewire::test(ListMutasiBanks::class)
        ->callAction('importMutasi', data: [
            'akun_id' => $bank->id,
            'data' => $teks,
        ])
        ->assertHasNoActionErrors();

    expect(MutasiBank::where('akun_id', $bank->id)->count())->toBe(2);

    $admin = MutasiBank::where('akun_id', $bank->id)->where('kredit', 15_000)->first();
    expect($admin)->not->toBeNull()
        ->and($admin->keterangan)->toBe('Biaya admin')
        ->and($admin->is_matched)->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// Saldo buku vs saldo rekening koran + selisih
// ─────────────────────────────────────────────────────────────────────────────

it('menghitung saldo buku dari ledger dan saldo koran dari mutasi', function () {
    userRekonsiliasi();
    $bank = akunBankBca();

    // Buku: jurnal D 5.000.000 pada akun bank → saldo buku = 5.000.000.
    JurnalUmum::factory()->debit(5_000_000)->create([
        'akun_id' => $bank->id,
        'tanggal' => '2026-07-10',
    ]);

    // Rekening koran: mutasi D 5.000.000 (tanpa kolom saldo) → saldo koran = 5.000.000.
    MutasiBank::factory()->debit(5_000_000)->create([
        'akun_id' => $bank->id,
        'tanggal' => '2026-07-10',
    ]);

    $component = Livewire::test(RekonsiliasiBank::class)
        ->set('tableFilters', [
            'akun' => ['akun_id' => $bank->id],
            'periode' => ['bulan' => '2026-07'],
        ]);

    $component->instance()->getTableRecords();
    $ringkasan = $component->get('ringkasan');

    expect((float) $ringkasan['saldo_buku'])->toBe(5_000_000.0)
        ->and((float) $ringkasan['saldo_koran'])->toBe(5_000_000.0)
        ->and((float) $ringkasan['selisih'])->toBe(0.0);
});

it('saldo koran memakai kolom saldo baris terakhir bila tersedia', function () {
    userRekonsiliasi();
    $bank = akunBankBca();

    MutasiBank::factory()->create([
        'akun_id' => $bank->id,
        'tanggal' => '2026-07-05',
        'debit' => 2_000_000,
        'kredit' => 0,
        'saldo' => 2_000_000,
    ]);
    MutasiBank::factory()->create([
        'akun_id' => $bank->id,
        'tanggal' => '2026-07-09',
        'debit' => 0,
        'kredit' => 500_000,
        'saldo' => 1_500_000,
    ]);

    $component = Livewire::test(RekonsiliasiBank::class)
        ->set('tableFilters', [
            'akun' => ['akun_id' => $bank->id],
            'periode' => ['bulan' => '2026-07'],
        ]);

    $component->instance()->getTableRecords();
    $ringkasan = $component->get('ringkasan');

    // Saldo koran = saldo baris terakhir = 1.500.000.
    expect((float) $ringkasan['saldo_koran'])->toBe(1_500_000.0);
});

it('menampilkan outstanding kedua sisi dan selisih saat tidak cocok', function () {
    userRekonsiliasi();
    $bank = akunBankBca();

    // Buku punya jurnal 3.000.000 yang belum tertaut → outstanding sisi buku.
    JurnalUmum::factory()->debit(3_000_000)->create([
        'akun_id' => $bank->id,
        'tanggal' => '2026-07-08',
    ]);

    // Rekening koran punya mutasi 1.000.000 belum cocok → outstanding sisi koran.
    MutasiBank::factory()->debit(1_000_000)->create([
        'akun_id' => $bank->id,
        'tanggal' => '2026-07-12',
        'is_matched' => false,
    ]);

    $component = Livewire::test(RekonsiliasiBank::class)
        ->set('tableFilters', [
            'akun' => ['akun_id' => $bank->id],
            'periode' => ['bulan' => '2026-07'],
        ]);

    $records = collect($component->instance()->getTableRecords());
    $ringkasan = $component->get('ringkasan');

    // Dua baris outstanding: satu koran, satu jurnal.
    expect($records)->toHaveCount(2)
        ->and($records->where('kategori', 'Outstanding Rekening Koran'))->toHaveCount(1)
        ->and($records->where('kategori', 'Outstanding Jurnal (Buku)'))->toHaveCount(1);

    // Saldo buku 3.000.000, saldo koran 1.000.000 → selisih 2.000.000.
    expect((float) $ringkasan['saldo_buku'])->toBe(3_000_000.0)
        ->and((float) $ringkasan['saldo_koran'])->toBe(1_000_000.0)
        ->and((float) $ringkasan['selisih'])->toBe(2_000_000.0)
        ->and((float) $ringkasan['outstanding_koran'])->toBe(1_000_000.0)
        ->and((float) $ringkasan['outstanding_jurnal'])->toBe(3_000_000.0);
});

// ─────────────────────────────────────────────────────────────────────────────
// Tandai cocok / batal cocok
// ─────────────────────────────────────────────────────────────────────────────

it('tandai cocok menyetel is_matched true dan menautkan jurnal', function () {
    userMutasiBank();
    $bank = akunBankBca();

    $jurnal = JurnalUmum::factory()->debit(1_000_000)->create([
        'akun_id' => $bank->id,
        'tanggal' => '2026-07-05',
    ]);

    $mutasi = MutasiBank::factory()->debit(1_000_000)->create([
        'akun_id' => $bank->id,
        'tanggal' => '2026-07-05',
        'is_matched' => false,
    ]);

    Livewire::test(ListMutasiBanks::class)
        ->callTableAction('tandaiCocok', $mutasi, data: [
            'jurnal_umum_id' => $jurnal->id,
        ])
        ->assertHasNoTableActionErrors();

    $mutasi->refresh();

    expect($mutasi->is_matched)->toBeTrue()
        ->and($mutasi->jurnal_umum_id)->toBe($jurnal->id);
});

it('mutasi yang dicocokkan tidak lagi muncul sebagai outstanding', function () {
    userRekonsiliasi();
    $bank = akunBankBca();

    $jurnal = JurnalUmum::factory()->debit(1_000_000)->create([
        'akun_id' => $bank->id,
        'tanggal' => '2026-07-05',
    ]);

    MutasiBank::factory()->debit(1_000_000)->create([
        'akun_id' => $bank->id,
        'tanggal' => '2026-07-05',
        'is_matched' => true,
        'jurnal_umum_id' => $jurnal->id,
    ]);

    $component = Livewire::test(RekonsiliasiBank::class)
        ->set('tableFilters', [
            'akun' => ['akun_id' => $bank->id],
            'periode' => ['bulan' => '2026-07'],
        ]);

    $records = collect($component->instance()->getTableRecords());

    // Tidak ada outstanding di kedua sisi → cocok sempurna.
    expect($records)->toHaveCount(0);
});

it('batal cocok mengembalikan is_matched false dan melepas jurnal', function () {
    userMutasiBank();
    $bank = akunBankBca();

    $jurnal = JurnalUmum::factory()->debit(1_000_000)->create([
        'akun_id' => $bank->id,
        'tanggal' => '2026-07-05',
    ]);

    $mutasi = MutasiBank::factory()->matched()->create([
        'akun_id' => $bank->id,
        'tanggal' => '2026-07-05',
        'debit' => 1_000_000,
        'kredit' => 0,
        'jurnal_umum_id' => $jurnal->id,
    ]);

    Livewire::test(ListMutasiBanks::class)
        ->callTableAction('batalCocok', $mutasi)
        ->assertHasNoTableActionErrors();

    $mutasi->refresh();

    expect($mutasi->is_matched)->toBeFalse()
        ->and($mutasi->jurnal_umum_id)->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// Ekspor PDF
// ─────────────────────────────────────────────────────────────────────────────

it('cetakPdf tersedia dan callable di RekonsiliasiBank', function () {
    userRekonsiliasi();
    $bank = akunBankBca();

    $component = Livewire::test(RekonsiliasiBank::class)
        ->set('tableFilters', [
            'akun' => ['akun_id' => $bank->id],
            'periode' => ['bulan' => '2026-07'],
        ]);

    expect(method_exists($component->instance(), 'getHeaderActions'))->toBeTrue();

    $component->callAction('cetakPdf')->assertHasNoActionErrors();
});
