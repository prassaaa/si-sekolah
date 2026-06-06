<?php

use App\Filament\Resources\SarprasPeminjamans\Pages\CreateSarprasPeminjaman;
use App\Filament\Resources\SarprasPeminjamans\Pages\EditSarprasPeminjaman;
use App\Filament\Resources\SarprasPeminjamans\Pages\ListSarprasPeminjamans;
use App\Models\Pegawai;
use App\Models\SarprasBarang;
use App\Models\SarprasPeminjaman;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permissions = [
        'ViewAny:SarprasPeminjaman', 'View:SarprasPeminjaman', 'Create:SarprasPeminjaman',
        'Update:SarprasPeminjaman', 'Delete:SarprasPeminjaman', 'DeleteAny:SarprasPeminjaman',
        'ForceDelete:SarprasPeminjaman', 'ForceDeleteAny:SarprasPeminjaman',
        'Restore:SarprasPeminjaman', 'RestoreAny:SarprasPeminjaman',
        'Replicate:SarprasPeminjaman', 'Reorder:SarprasPeminjaman',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    $this->actingAs($user);
});

it('renders the list page', function () {
    Livewire::test(ListSarprasPeminjamans::class)->assertOk();
});

it('lists peminjaman records', function () {
    // Each factory call creates its own tersedia barang; booted hook fires and sets it to dipinjam.
    $records = SarprasPeminjaman::factory()->count(3)->create();

    Livewire::test(ListSarprasPeminjamans::class)
        ->assertCanSeeTableRecords($records);
});

it('creates peminjaman and sets barang status to dipinjam', function () {
    $siswa = Siswa::factory()->create();
    $petugas = Pegawai::factory()->create();
    $barang = SarprasBarang::factory()->aset()->tersedia()->create();

    Livewire::test(CreateSarprasPeminjaman::class)
        ->fillForm([
            'sarpras_barang_id' => $barang->id,
            'peminjam_type' => Siswa::class,
            'peminjam_id' => $siswa->id,
            'jumlah' => 1,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_harus_kembali' => now()->addDays(7)->toDateString(),
            'kondisi_pinjam' => 'baik',
            'petugas_id' => $petugas->id,
        ])
        ->call('create')
        ->assertNotified();

    $this->assertDatabaseHas('sarpras_peminjamans', [
        'sarpras_barang_id' => $barang->id,
        'peminjam_type' => Siswa::class,
        'peminjam_id' => $siswa->id,
        'status' => 'dipinjam',
    ]);

    $this->assertDatabaseHas('sarpras_barangs', [
        'id' => $barang->id,
        'status' => 'dipinjam',
    ]);
});

it('kembalikan action sets barang back to tersedia and updates kondisi', function () {
    // Factory creates a tersedia barang; booted hook sets it to dipinjam after create.
    // Use a future tanggal_harus_kembali so kembalikan() sets status to dikembalikan (not terlambat).
    $peminjaman = SarprasPeminjaman::factory()->create([
        'tanggal_pinjam' => now()->toDateString(),
        'tanggal_harus_kembali' => now()->addDays(7)->toDateString(),
    ]);

    $barang = $peminjaman->barang;

    Livewire::test(ListSarprasPeminjamans::class)
        ->callTableAction('kembalikan', $peminjaman, data: ['kondisi_kembali' => 'baik'])
        ->assertNotified();

    expect($barang->fresh()->status)->toBe('tersedia');
    expect($barang->fresh()->kondisi)->toBe('baik');
    expect($peminjaman->fresh()->status)->toBe('dikembalikan');
    expect($peminjaman->fresh()->kondisi_kembali)->toBe('baik');
});

it('cannot borrow a barang that is not tersedia', function () {
    $siswa = Siswa::factory()->create();

    // Create a peminjaman first (booted hook sets barang to dipinjam).
    // Then try to create another peminjaman for the same barang.
    $firstPeminjaman = SarprasPeminjaman::factory()->create();
    $barang = $firstPeminjaman->barang;

    // barang is now dipinjam; trying to borrow it again should fail.
    expect($barang->fresh()->status)->toBe('dipinjam');

    Livewire::test(CreateSarprasPeminjaman::class)
        ->fillForm([
            'sarpras_barang_id' => $barang->id,
            'peminjam_type' => Siswa::class,
            'peminjam_id' => $siswa->id,
            'jumlah' => 1,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_harus_kembali' => now()->addDays(7)->toDateString(),
            'kondisi_pinjam' => 'baik',
        ])
        ->call('create')
        ->assertHasErrors();

    // No second record created for the same barang
    expect(SarprasPeminjaman::where('sarpras_barang_id', $barang->id)->count())->toBe(1);
});

it('filters by status', function () {
    // Each factory()->create() uses its own tersedia barang; booted hook sets it to dipinjam.
    $peminjamanDipinjam = SarprasPeminjaman::factory()->create();

    // For dikembalikan: use factory state then directly update status in DB to avoid booted hook issues.
    $peminjamanDikembalikan = SarprasPeminjaman::factory()->dikembalikan()->create();

    Livewire::test(ListSarprasPeminjamans::class)
        ->filterTable('status', ['dipinjam'])
        ->assertCanSeeTableRecords([$peminjamanDipinjam])
        ->assertCanNotSeeTableRecords([$peminjamanDikembalikan]);
});

it('renders the create page', function () {
    Livewire::test(CreateSarprasPeminjaman::class)->assertOk();
});

it('renders the edit page', function () {
    // Factory creates its own tersedia barang; booted hook sets it to dipinjam.
    $peminjaman = SarprasPeminjaman::factory()->create();

    Livewire::test(EditSarprasPeminjaman::class, ['record' => $peminjaman->id])
        ->assertOk();
});
