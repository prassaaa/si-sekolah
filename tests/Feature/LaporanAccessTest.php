<?php

use App\Filament\Pages\LaporanGaji;
use App\Filament\Pages\LaporanInventaris;
use App\Filament\Pages\Neraca;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create all required page permissions (normally done by RoleSeeder)
    $pages = [
        'Neraca', 'LabaRugi', 'BukuBesar', 'PerubahanModal', 'ArusKasBank',
        'LaporanJurnal', 'LaporanDebitKredit', 'LaporanKeuangan',
        'LaporanPembayaran', 'LaporanPembayaranPerKelas', 'LaporanPembayaranPerTanggal',
        'LaporanTagihanSiswa', 'LaporanUnitPos', 'LaporanTabungan',
        'LaporanGaji', 'KirimNotifGaji', 'KirimTagihan', 'LaporanPenyusutan',
        'LaporanInventaris', 'LaporanKondisiSarpras', 'LaporanPemeliharaanSarpras',
        'LaporanPeminjamanSarpras', 'LaporanSiswa', 'LaporanTahfidz',
        'KirimNotifPresensi', 'MonitorGerbang',
    ];

    foreach ($pages as $page) {
        Permission::firstOrCreate(['name' => "View:{$page}"]);
    }

    // Create roles
    Role::firstOrCreate(['name' => 'bendahara']);
    Role::firstOrCreate(['name' => 'petugas_sarpras']);
    Role::firstOrCreate(['name' => 'guru']);
});

it('user tanpa role tidak bisa akses Neraca', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    expect(Neraca::canAccess())->toBeFalse();
});

it('user tanpa role tidak bisa akses LaporanGaji', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    expect(LaporanGaji::canAccess())->toBeFalse();
});

it('user tanpa role tidak bisa akses LaporanInventaris', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    expect(LaporanInventaris::canAccess())->toBeFalse();
});

it('guru tidak bisa akses Neraca', function () {
    $user = User::factory()->create();
    $user->assignRole('guru');
    $this->actingAs($user);

    expect(Neraca::canAccess())->toBeFalse();
});

it('guru tidak bisa akses LaporanGaji', function () {
    $user = User::factory()->create();
    $user->assignRole('guru');
    $this->actingAs($user);

    expect(LaporanGaji::canAccess())->toBeFalse();
});

it('bendahara bisa akses Neraca', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('View:Neraca');
    $this->actingAs($user);

    expect(Neraca::canAccess())->toBeTrue();
});

it('bendahara bisa akses LaporanGaji', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('View:LaporanGaji');
    $this->actingAs($user);

    expect(LaporanGaji::canAccess())->toBeTrue();
});

it('bendahara bisa render Neraca page via Livewire', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('View:Neraca');
    $this->actingAs($user);

    Livewire::test(Neraca::class)->assertOk();
});

it('bendahara bisa render LaporanGaji page via Livewire', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('View:LaporanGaji');
    $this->actingAs($user);

    Livewire::test(LaporanGaji::class)->assertOk();
});

it('petugas_sarpras bisa akses LaporanInventaris', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('View:LaporanInventaris');
    $this->actingAs($user);

    expect(LaporanInventaris::canAccess())->toBeTrue();
});

it('petugas_sarpras tidak bisa akses Neraca', function () {
    $user = User::factory()->create();
    $user->assignRole('petugas_sarpras');
    $this->actingAs($user);

    expect(Neraca::canAccess())->toBeFalse();
});
