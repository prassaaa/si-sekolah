<?php

use App\Filament\Resources\SarprasPenghapusans\Pages\CreateSarprasPenghapusan;
use App\Filament\Resources\SarprasPenghapusans\Pages\ListSarprasPenghapusans;
use App\Models\SarprasBarang;
use App\Models\SarprasPenghapusan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permissions = [
        'ViewAny:SarprasPenghapusan', 'View:SarprasPenghapusan', 'Create:SarprasPenghapusan',
        'Update:SarprasPenghapusan', 'Delete:SarprasPenghapusan', 'DeleteAny:SarprasPenghapusan',
        'ForceDelete:SarprasPenghapusan', 'ForceDeleteAny:SarprasPenghapusan',
        'Restore:SarprasPenghapusan', 'RestoreAny:SarprasPenghapusan',
        'Replicate:SarprasPenghapusan', 'Reorder:SarprasPenghapusan',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    $this->actingAs($user);
});

it('renders the list page', function () {
    Livewire::test(ListSarprasPenghapusans::class)->assertOk();
});

it('creates a penghapusan and auto-generates nomor', function () {
    $barang = SarprasBarang::factory()->create(['is_active' => true]);

    Livewire::test(CreateSarprasPenghapusan::class)
        ->fillForm([
            'sarpras_barang_id' => $barang->id,
            'tanggal' => now()->toDateString(),
            'alasan' => 'rusak_berat',
            'jumlah' => 1,
            'nilai_sisa' => 0,
            'metode' => 'dibuang',
            'status' => 'diajukan',
        ])
        ->call('create')
        ->assertNotified();

    $penghapusan = SarprasPenghapusan::where('sarpras_barang_id', $barang->id)->first();

    expect($penghapusan)->not->toBeNull()
        ->and($penghapusan->nomor)->toStartWith('PHP-')
        ->and($penghapusan->status)->toBe('diajukan');
});

it('setujui action marks barang as dihapus', function () {
    $barang = SarprasBarang::factory()->create(['is_active' => true, 'status' => 'tersedia']);
    $penghapusan = SarprasPenghapusan::factory()->create([
        'sarpras_barang_id' => $barang->id,
        'status' => 'diajukan',
    ]);

    Livewire::test(ListSarprasPenghapusans::class)
        ->callTableAction('setujui', $penghapusan)
        ->assertNotified();

    expect($penghapusan->fresh()->status)->toBe('disetujui')
        ->and($barang->fresh()->status)->toBe('dihapus')
        ->and($barang->fresh()->is_active)->toBeFalse();
});

it('setujui action is idempotent', function () {
    $barang = SarprasBarang::factory()->create(['is_active' => false, 'status' => 'dihapus']);
    $penghapusan = SarprasPenghapusan::factory()->create([
        'sarpras_barang_id' => $barang->id,
        'status' => 'disetujui',
    ]);

    // Calling setujui on an already-approved record should not throw
    $penghapusan->setujui();

    expect($penghapusan->fresh()->status)->toBe('disetujui')
        ->and($barang->fresh()->status)->toBe('dihapus');
});
