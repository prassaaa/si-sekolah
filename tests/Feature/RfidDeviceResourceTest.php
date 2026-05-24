<?php

use App\Filament\Resources\RfidDevices\Pages\CreateRfidDevice;
use App\Filament\Resources\RfidDevices\Pages\ListRfidDevices;
use App\Models\RfidDevice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permissions = [
        'ViewAny:RfidDevice', 'View:RfidDevice', 'Create:RfidDevice',
        'Update:RfidDevice', 'Delete:RfidDevice', 'DeleteAny:RfidDevice',
        'ForceDelete:RfidDevice', 'ForceDeleteAny:RfidDevice',
        'Restore:RfidDevice', 'RestoreAny:RfidDevice',
        'Replicate:RfidDevice', 'Reorder:RfidDevice',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    $this->actingAs($user);
});

it('renders the list page', function () {
    Livewire::test(ListRfidDevices::class)->assertOk();
});

it('lists device records', function () {
    $records = RfidDevice::factory()->count(2)->create();

    Livewire::test(ListRfidDevices::class)
        ->assertCanSeeTableRecords($records);
});

it('creates a device with hashed token', function () {
    Livewire::test(CreateRfidDevice::class)
        ->fillForm([
            'nama' => 'Test Reader',
            'kode' => 'TEST-01',
            'jenis' => 'gerbang_masuk',
            'lokasi' => 'Gerbang Test',
            'is_active' => true,
        ])
        ->call('create')
        ->assertNotified();

    $device = RfidDevice::where('kode', 'TEST-01')->first();
    expect($device)->not->toBeNull();
    expect($device->api_token)->not->toBeEmpty();
    expect($device->api_token)->not->toBe('TEST-01');
});

it('rejects duplicate kode', function () {
    RfidDevice::factory()->create(['kode' => 'GERBANG-01']);

    Livewire::test(CreateRfidDevice::class)
        ->fillForm([
            'nama' => 'Test',
            'kode' => 'GERBANG-01',
            'jenis' => 'gerbang_masuk',
        ])
        ->call('create')
        ->assertHasFormErrors(['kode']);
});

it('rejects invalid kode format with special chars', function () {
    Livewire::test(CreateRfidDevice::class)
        ->fillForm([
            'nama' => 'Test',
            'kode' => 'GERBANG@01!',
            'jenis' => 'gerbang_masuk',
        ])
        ->call('create')
        ->assertHasFormErrors(['kode']);
});

it('regenerateToken action produces a new hash', function () {
    $device = RfidDevice::factory()->create();
    $oldHash = $device->api_token;

    $plain = $device->generateToken();

    expect($plain)->toHaveLength(60);
    expect($device->fresh()->api_token)->not->toBe($oldHash);
    expect(Hash::check($plain, $device->fresh()->api_token))->toBeTrue();
});
