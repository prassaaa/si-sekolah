<?php

use App\Filament\Resources\Pembayarans\Pages\EditPembayaran;
use App\Models\Pembayaran;
use App\Models\TagihanSiswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permissions = [
        'ViewAny:Pembayaran', 'View:Pembayaran', 'Create:Pembayaran',
        'Update:Pembayaran', 'Delete:Pembayaran', 'DeleteAny:Pembayaran',
        'ForceDelete:Pembayaran', 'ForceDeleteAny:Pembayaran',
        'Restore:Pembayaran', 'RestoreAny:Pembayaran',
        'Replicate:Pembayaran', 'Reorder:Pembayaran',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    $this->actingAs($user);
});

function freshTagihan(int $total = 500000): TagihanSiswa
{
    return TagihanSiswa::factory()->create([
        'nominal' => $total,
        'diskon' => 0,
        'total_tagihan' => $total,
        'total_terbayar' => 0,
        'sisa_tagihan' => $total,
        'status' => 'belum_bayar',
    ]);
}

it('allows editing an applied payment by adding back its own applied amount', function () {
    $tagihan = freshTagihan(500000);

    $pembayaran = Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'jumlah_bayar' => 500000,
        'status' => 'berhasil',
    ]);

    Livewire::test(EditPembayaran::class, ['record' => $pembayaran->getRouteKey()])
        ->fillForm(['jumlah_bayar' => 400000])
        ->call('save')
        ->assertHasNoFormErrors();

    $tagihan->refresh();

    expect((float) $tagihan->total_terbayar)->toBe(400000.0)
        ->and((float) $tagihan->sisa_tagihan)->toBe(100000.0);
});

it('rejects an edit that exceeds available sisa', function () {
    $tagihan = freshTagihan(500000);

    $pembayaran = Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'jumlah_bayar' => 200000,
        'status' => 'berhasil',
    ]);

    Livewire::test(EditPembayaran::class, ['record' => $pembayaran->getRouteKey()])
        ->fillForm(['jumlah_bayar' => 900000])
        ->call('save')
        ->assertHasErrors(['jumlah_bayar']);

    $tagihan->refresh();

    expect((float) $tagihan->total_terbayar)->toBe(200000.0);
});

it('validates a pending to berhasil transition against the full available sisa', function () {
    $tagihan = freshTagihan(500000);

    $pembayaran = Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'jumlah_bayar' => 500000,
        'status' => 'pending',
    ]);

    Livewire::test(EditPembayaran::class, ['record' => $pembayaran->getRouteKey()])
        ->fillForm(['status' => 'berhasil'])
        ->call('save')
        ->assertHasNoFormErrors();

    $tagihan->refresh();

    expect((float) $tagihan->total_terbayar)->toBe(500000.0)
        ->and($tagihan->status)->toBe('lunas');
});
