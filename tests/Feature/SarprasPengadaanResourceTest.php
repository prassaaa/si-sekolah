<?php

use App\Filament\Resources\SarprasPengadaans\Pages\CreateSarprasPengadaan;
use App\Filament\Resources\SarprasPengadaans\Pages\EditSarprasPengadaan;
use App\Filament\Resources\SarprasPengadaans\Pages\ListSarprasPengadaans;
use App\Models\SarprasBarang;
use App\Models\SarprasKategori;
use App\Models\SarprasPengadaan;
use App\Models\SarprasPengadaanItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permissions = [
        'ViewAny:SarprasPengadaan', 'View:SarprasPengadaan', 'Create:SarprasPengadaan',
        'Update:SarprasPengadaan', 'Delete:SarprasPengadaan', 'DeleteAny:SarprasPengadaan',
        'ForceDelete:SarprasPengadaan', 'ForceDeleteAny:SarprasPengadaan',
        'Restore:SarprasPengadaan', 'RestoreAny:SarprasPengadaan',
        'Replicate:SarprasPengadaan', 'Reorder:SarprasPengadaan',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    $this->actingAs($user);
});

it('renders the list page', function () {
    Livewire::test(ListSarprasPengadaans::class)->assertOk();
});

it('lists pengadaan records', function () {
    $records = SarprasPengadaan::factory()->count(3)->create();

    Livewire::test(ListSarprasPengadaans::class)
        ->assertCanSeeTableRecords($records);
});

it('renders the create page', function () {
    Livewire::test(CreateSarprasPengadaan::class)->assertOk();
});

it('creates a pengadaan with items and persists correctly', function () {
    $kategori = SarprasKategori::factory()->create();

    Livewire::test(CreateSarprasPengadaan::class)
        ->fillForm([
            'tanggal' => now()->toDateString(),
            'sumber_dana' => 'bos',
            'penyedia' => 'Toko ABC',
            'status' => 'draft',
            'keterangan' => 'Pengadaan rutin',
            'items' => [
                [
                    'nama_barang' => 'Kursi Kelas',
                    'sarpras_kategori_id' => $kategori->id,
                    'jumlah' => 5,
                    'satuan' => 'unit',
                    'harga_satuan' => 200000,
                ],
            ],
        ])
        ->call('create')
        ->assertNotified();

    $pengadaan = SarprasPengadaan::query()->where('penyedia', 'Toko ABC')->first();
    expect($pengadaan)->not->toBeNull();
    expect($pengadaan->items)->toHaveCount(1);

    $item = $pengadaan->items->first();
    expect($item->nama_barang)->toBe('Kursi Kelas');
    expect($item->jumlah)->toBe(5);
    expect((float) $item->harga_satuan)->toBe(200000.0);
    expect((float) $item->subtotal)->toBe(1000000.0);
});

it('recalculates total_biaya from items', function () {
    $kategori = SarprasKategori::factory()->create();
    $pengadaan = SarprasPengadaan::factory()->create();

    SarprasPengadaanItem::factory()->create([
        'sarpras_pengadaan_id' => $pengadaan->id,
        'sarpras_kategori_id' => $kategori->id,
        'jumlah' => 2,
        'harga_satuan' => 500000,
        'subtotal' => '1000000.00',
    ]);

    SarprasPengadaanItem::factory()->create([
        'sarpras_pengadaan_id' => $pengadaan->id,
        'sarpras_kategori_id' => $kategori->id,
        'jumlah' => 3,
        'harga_satuan' => 100000,
        'subtotal' => '300000.00',
    ]);

    $pengadaan->recalculateTotal();

    expect((float) $pengadaan->fresh()->total_biaya)->toBe(1300000.0);
});

it('renders the edit page', function () {
    $pengadaan = SarprasPengadaan::factory()->create();

    Livewire::test(EditSarprasPengadaan::class, ['record' => $pengadaan->id])
        ->assertOk();
});

it('terima action creates SarprasBarang stock from items', function () {
    $kategori = SarprasKategori::factory()->create();
    $pengadaan = SarprasPengadaan::factory()->disetujui()->create();

    $item = SarprasPengadaanItem::factory()->create([
        'sarpras_pengadaan_id' => $pengadaan->id,
        'sarpras_kategori_id' => $kategori->id,
        'nama_barang' => 'Spidol Whiteboard',
        'jumlah' => 10,
        'satuan' => 'box',
        'harga_satuan' => 50000,
        'subtotal' => '500000.00',
    ]);

    $pengadaan->terima();

    $pengadaan->refresh();
    expect($pengadaan->status)->toBe('diterima');

    $kodeInventaris = 'INV-'.$pengadaan->nomor.'-'.$item->id;
    $barang = SarprasBarang::query()->where('kode_inventaris', $kodeInventaris)->first();

    expect($barang)->not->toBeNull();
    expect($barang->nama)->toBe('Spidol Whiteboard');
    expect($barang->jumlah)->toBe(10);
    expect($barang->satuan)->toBe('box');
    expect($barang->sarpras_kategori_id)->toBe($kategori->id);
    expect($barang->status)->toBe('tersedia');
    expect($barang->kondisi)->toBe('baik');
});

it('terima is idempotent — calling twice does not double stock', function () {
    $kategori = SarprasKategori::factory()->create();
    $pengadaan = SarprasPengadaan::factory()->disetujui()->create();

    $item = SarprasPengadaanItem::factory()->create([
        'sarpras_pengadaan_id' => $pengadaan->id,
        'sarpras_kategori_id' => $kategori->id,
        'nama_barang' => 'Kertas A4',
        'jumlah' => 5,
        'satuan' => 'rim',
        'harga_satuan' => 80000,
        'subtotal' => '400000.00',
    ]);

    // First call — creates SarprasBarang with jumlah = 5
    $pengadaan->terima();

    $kodeInventaris = 'INV-'.$pengadaan->nomor.'-'.$item->id;
    $barang = SarprasBarang::query()->where('kode_inventaris', $kodeInventaris)->first();
    expect($barang->jumlah)->toBe(5);

    // Second call — status is already 'diterima', should be a no-op
    $pengadaan->terima();

    $barang->refresh();
    expect($barang->jumlah)->toBe(5);
    expect(SarprasBarang::query()->where('kode_inventaris', $kodeInventaris)->count())->toBe(1);
});

it('filters by status', function () {
    $draft = SarprasPengadaan::factory()->create(['status' => 'draft']);
    $disetujui = SarprasPengadaan::factory()->disetujui()->create();

    Livewire::test(ListSarprasPengadaans::class)
        ->filterTable('status', ['disetujui'])
        ->assertCanSeeTableRecords([$disetujui])
        ->assertCanNotSeeTableRecords([$draft]);
});

it('filters by sumber_dana', function () {
    $bos = SarprasPengadaan::factory()->create(['sumber_dana' => 'bos']);
    $komite = SarprasPengadaan::factory()->create(['sumber_dana' => 'komite']);

    Livewire::test(ListSarprasPengadaans::class)
        ->filterTable('sumber_dana', ['bos'])
        ->assertCanSeeTableRecords([$bos])
        ->assertCanNotSeeTableRecords([$komite]);
});

it('terima row action is visible only for disetujui records', function () {
    $draft = SarprasPengadaan::factory()->create(['status' => 'draft']);
    $disetujui = SarprasPengadaan::factory()->disetujui()->create();

    Livewire::test(ListSarprasPengadaans::class)
        ->assertTableActionVisible('terima', $disetujui)
        ->assertTableActionHidden('terima', $draft);
});
