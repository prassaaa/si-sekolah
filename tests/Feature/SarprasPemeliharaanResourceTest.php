<?php

use App\Filament\Resources\SarprasPemeliharaans\Pages\CreateSarprasPemeliharaan;
use App\Filament\Resources\SarprasPemeliharaans\Pages\ListSarprasPemeliharaans;
use App\Models\SarprasBarang;
use App\Models\SarprasPemeliharaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permissions = [
        'ViewAny:SarprasPemeliharaan', 'View:SarprasPemeliharaan', 'Create:SarprasPemeliharaan',
        'Update:SarprasPemeliharaan', 'Delete:SarprasPemeliharaan', 'DeleteAny:SarprasPemeliharaan',
        'ForceDelete:SarprasPemeliharaan', 'ForceDeleteAny:SarprasPemeliharaan',
        'Restore:SarprasPemeliharaan', 'RestoreAny:SarprasPemeliharaan',
        'Replicate:SarprasPemeliharaan', 'Reorder:SarprasPemeliharaan',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    $this->actingAs($user);
});

it('renders the list page', function () {
    Livewire::test(ListSarprasPemeliharaans::class)->assertOk();
});

it('renders the create page', function () {
    Livewire::test(CreateSarprasPemeliharaan::class)->assertOk();
});

it('creates a pemeliharaan record with auto-generated nomor', function () {
    $barang = SarprasBarang::factory()->create();

    Livewire::test(CreateSarprasPemeliharaan::class)
        ->fillForm([
            'sarpras_barang_id' => $barang->id,
            'jenis' => 'perbaikan',
            'tanggal' => now()->toDateString(),
            'deskripsi_masalah' => 'Kerusakan pada layar monitor',
            'pelaksana' => 'internal',
            'status' => 'dijadwalkan',
        ])
        ->call('create')
        ->assertNotified();

    $pemeliharaan = SarprasPemeliharaan::where('sarpras_barang_id', $barang->id)->first();

    expect($pemeliharaan)->not->toBeNull();
    expect($pemeliharaan->nomor)->toStartWith('PML-');
    expect($pemeliharaan->jenis)->toBe('perbaikan');
    expect($pemeliharaan->status)->toBe('dijadwalkan');
});

it('sets barang status to perbaikan when status changes to proses', function () {
    $barang = SarprasBarang::factory()->create(['status' => 'tersedia']);

    $pemeliharaan = SarprasPemeliharaan::factory()->create([
        'sarpras_barang_id' => $barang->id,
        'status' => 'dijadwalkan',
    ]);

    $pemeliharaan->update(['status' => 'proses']);

    expect($barang->fresh()->status)->toBe('perbaikan');
});

it('restores barang status to tersedia when status changes to selesai', function () {
    $barang = SarprasBarang::factory()->create(['status' => 'perbaikan']);

    $pemeliharaan = SarprasPemeliharaan::factory()->proses()->create([
        'sarpras_barang_id' => $barang->id,
        'kondisi_sesudah' => 'baik',
    ]);

    $pemeliharaan->update([
        'status' => 'selesai',
        'kondisi_sesudah' => 'baik',
    ]);

    expect($barang->fresh()->status)->toBe('tersedia');
    expect($barang->fresh()->kondisi)->toBe('baik');
});

it('lists pemeliharaan records', function () {
    $records = SarprasPemeliharaan::factory()->count(3)->create();

    Livewire::test(ListSarprasPemeliharaans::class)
        ->assertCanSeeTableRecords($records);
});
