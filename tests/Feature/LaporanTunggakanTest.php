<?php

use App\Filament\Pages\LaporanTunggakan;
use App\Models\TagihanSiswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'View:LaporanTunggakan']);
    Role::firstOrCreate(['name' => 'bendahara']);
    Role::firstOrCreate(['name' => 'guru']);
});

// --- Akses ---

it('bendahara bisa akses halaman LaporanTunggakan', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('View:LaporanTunggakan');
    $this->actingAs($user);

    expect(LaporanTunggakan::canAccess())->toBeTrue();
});

it('guru tidak bisa akses halaman LaporanTunggakan', function () {
    $user = User::factory()->create();
    $user->assignRole('guru');
    $this->actingAs($user);

    expect(LaporanTunggakan::canAccess())->toBeFalse();
});

it('halaman LaporanTunggakan dapat dirender oleh bendahara', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('View:LaporanTunggakan');
    $this->actingAs($user);

    Livewire::test(LaporanTunggakan::class)->assertOk();
});

// --- Bucket umur ---

it('tagihan dengan jatuh tempo 45 hari lalu masuk bucket 31-60 hari', function () {
    $tanggalJatuhTempo = Carbon::now()->subDays(45);

    $bucket = LaporanTunggakan::bucketUmur($tanggalJatuhTempo);

    expect($bucket)->toBe('31-60 hari');
});

it('tagihan dengan jatuh tempo 15 hari lalu masuk bucket 1-30 hari', function () {
    expect(LaporanTunggakan::bucketUmur(Carbon::now()->subDays(15)))->toBe('1-30 hari');
});

it('tagihan dengan jatuh tempo 70 hari lalu masuk bucket 61-90 hari', function () {
    expect(LaporanTunggakan::bucketUmur(Carbon::now()->subDays(70)))->toBe('61-90 hari');
});

it('tagihan dengan jatuh tempo 100 hari lalu masuk bucket >90 hari', function () {
    expect(LaporanTunggakan::bucketUmur(Carbon::now()->subDays(100)))->toBe('>90 hari');
});

it('tagihan yang belum melewati jatuh tempo masuk Belum Jatuh Tempo', function () {
    expect(LaporanTunggakan::bucketUmur(Carbon::now()->addDays(5)))->toBe('Belum Jatuh Tempo');
});

// --- Data muncul di tabel ---

it('tagihan belum bayar yang lewat jatuh tempo muncul di laporan', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('View:LaporanTunggakan');
    $this->actingAs($user);

    $tagihan = TagihanSiswa::factory()->belumBayar()->create([
        'tanggal_jatuh_tempo' => Carbon::now()->subDays(45),
        'total_tagihan' => 300000,
        'sisa_tagihan' => 300000,
    ]);

    Livewire::test(LaporanTunggakan::class)
        ->assertCanSeeTableRecords([$tagihan]);
});

it('tagihan sebagian yang lewat jatuh tempo muncul di laporan', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('View:LaporanTunggakan');
    $this->actingAs($user);

    $tagihan = TagihanSiswa::factory()->create([
        'status' => 'sebagian',
        'tanggal_jatuh_tempo' => Carbon::now()->subDays(45),
        'total_tagihan' => 300000,
        'total_terbayar' => 100000,
        'sisa_tagihan' => 200000,
    ]);

    Livewire::test(LaporanTunggakan::class)
        ->assertCanSeeTableRecords([$tagihan]);
});

// --- Data TIDAK muncul ---

it('tagihan lunas tidak muncul di laporan', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('View:LaporanTunggakan');
    $this->actingAs($user);

    $tagihan = TagihanSiswa::factory()->lunas()->create([
        'tanggal_jatuh_tempo' => Carbon::now()->subDays(45),
    ]);

    Livewire::test(LaporanTunggakan::class)
        ->assertCanNotSeeTableRecords([$tagihan]);
});

it('tagihan batal tidak muncul di laporan', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('View:LaporanTunggakan');
    $this->actingAs($user);

    $tagihan = TagihanSiswa::factory()->create([
        'status' => 'batal',
        'tanggal_jatuh_tempo' => Carbon::now()->subDays(45),
        'sisa_tagihan' => 250000,
    ]);

    Livewire::test(LaporanTunggakan::class)
        ->assertCanNotSeeTableRecords([$tagihan]);
});

it('tagihan belum bayar yang belum melewati jatuh tempo tidak muncul di laporan', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('View:LaporanTunggakan');
    $this->actingAs($user);

    $tagihan = TagihanSiswa::factory()->belumBayar()->create([
        'tanggal_jatuh_tempo' => Carbon::now()->addDays(10),
        'total_tagihan' => 300000,
        'sisa_tagihan' => 300000,
    ]);

    Livewire::test(LaporanTunggakan::class)
        ->assertCanNotSeeTableRecords([$tagihan]);
});

// --- Total tunggakan ---

it('total tunggakan sama dengan sum sisa_tagihan yang menunggak', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('View:LaporanTunggakan');
    $this->actingAs($user);

    TagihanSiswa::factory()->belumBayar()->create([
        'tanggal_jatuh_tempo' => Carbon::now()->subDays(20),
        'total_tagihan' => 200000,
        'sisa_tagihan' => 200000,
    ]);

    TagihanSiswa::factory()->create([
        'status' => 'sebagian',
        'tanggal_jatuh_tempo' => Carbon::now()->subDays(50),
        'total_tagihan' => 300000,
        'total_terbayar' => 100000,
        'sisa_tagihan' => 200000,
    ]);

    // Tagihan lunas tidak dihitung
    TagihanSiswa::factory()->lunas()->create([
        'tanggal_jatuh_tempo' => Carbon::now()->subDays(10),
    ]);

    $expectedTotal = TagihanSiswa::query()
        ->whereIn('status', ['belum_bayar', 'sebagian'])
        ->where('sisa_tagihan', '>', 0)
        ->where('tanggal_jatuh_tempo', '<', Carbon::now())
        ->sum('sisa_tagihan');

    expect((int) $expectedTotal)->toBe(400000);
});

// --- Action cetakPdf ---

it('action cetakPdf ada di halaman LaporanTunggakan', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('View:LaporanTunggakan');
    $this->actingAs($user);

    Livewire::test(LaporanTunggakan::class)
        ->assertActionExists('cetakPdf');
});
