<?php

use App\Models\SarprasBarang;
use App\Models\SarprasPeminjaman;
use App\Models\Sekolah;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('return on time sets denda 0 and status dikembalikan', function () {
    $peminjaman = SarprasPeminjaman::factory()->create([
        'tanggal_pinjam' => now()->subDays(3)->toDateString(),
        'tanggal_harus_kembali' => now()->addDays(4)->toDateString(),
        'jumlah' => 1,
    ]);

    $peminjaman->kembalikan('baik');

    $fresh = $peminjaman->fresh();
    expect($fresh->hari_telat)->toBe(0);
    expect((float) $fresh->denda)->toBe(0.0);
    expect($fresh->status)->toBe('dikembalikan');
});

it('return N days late with tarif set computes exact denda and sets status terlambat', function () {
    Sekolah::factory()->create([
        'tarif_denda_sarpras_per_hari' => 1000,
        'maks_denda_persen' => 50,
    ]);

    $barang = SarprasBarang::factory()->aset()->tersedia()->create([
        'harga_perolehan' => 500000,
    ]);

    $peminjaman = SarprasPeminjaman::factory()->create([
        'sarpras_barang_id' => $barang->id,
        'tanggal_pinjam' => now()->subDays(10)->toDateString(),
        'tanggal_harus_kembali' => now()->subDays(3)->toDateString(),
        'jumlah' => 2,
    ]);

    $peminjaman->kembalikan('baik');

    $fresh = $peminjaman->fresh();

    // hari_telat = 3, tarif = 1000, jumlah = 2 → denda = 3 × 1000 × 2 = 6000
    expect($fresh->hari_telat)->toBe(3);
    expect((float) $fresh->denda)->toBe(6000.0);
    expect($fresh->status)->toBe('terlambat');
});

it('denda is capped at maks_denda_persen of harga_perolehan', function () {
    Sekolah::factory()->create([
        'tarif_denda_sarpras_per_hari' => 10000,
        'maks_denda_persen' => 50,
    ]);

    $barang = SarprasBarang::factory()->aset()->tersedia()->create([
        'harga_perolehan' => 100000,
    ]);

    $peminjaman = SarprasPeminjaman::factory()->create([
        'sarpras_barang_id' => $barang->id,
        'tanggal_pinjam' => now()->subDays(30)->toDateString(),
        'tanggal_harus_kembali' => now()->subDays(20)->toDateString(),
        'jumlah' => 1,
    ]);

    $peminjaman->kembalikan('baik');

    $fresh = $peminjaman->fresh();

    // Uncapped: 20 × 10000 × 1 = 200000; maks: 50% × 100000 = 50000
    expect($fresh->hari_telat)->toBe(20);
    expect((float) $fresh->denda)->toBe(50000.0);
    expect($fresh->status)->toBe('terlambat');
});

it('denda is 0 when tarif is 0 even if late', function () {
    Sekolah::factory()->create([
        'tarif_denda_sarpras_per_hari' => 0,
        'maks_denda_persen' => 50,
    ]);

    $peminjaman = SarprasPeminjaman::factory()->create([
        'tanggal_pinjam' => now()->subDays(10)->toDateString(),
        'tanggal_harus_kembali' => now()->subDays(3)->toDateString(),
        'jumlah' => 1,
    ]);

    $peminjaman->kembalikan('baik');

    $fresh = $peminjaman->fresh();
    expect($fresh->hari_telat)->toBe(3);
    expect((float) $fresh->denda)->toBe(0.0);
    expect($fresh->status)->toBe('terlambat');
});

it('denda applies to pegawai borrowers as well', function () {
    Sekolah::factory()->create([
        'tarif_denda_sarpras_per_hari' => 2000,
        'maks_denda_persen' => 50,
    ]);

    $barang = SarprasBarang::factory()->aset()->tersedia()->create([
        'harga_perolehan' => 500000,
    ]);

    $peminjaman = SarprasPeminjaman::factory()->untukPegawai()->create([
        'sarpras_barang_id' => $barang->id,
        'tanggal_pinjam' => now()->subDays(5)->toDateString(),
        'tanggal_harus_kembali' => now()->subDays(2)->toDateString(),
        'jumlah' => 1,
    ]);

    $peminjaman->kembalikan('baik');

    $fresh = $peminjaman->fresh();

    // 2 × 2000 × 1 = 4000
    expect($fresh->hari_telat)->toBe(2);
    expect((float) $fresh->denda)->toBe(4000.0);
    expect($fresh->status)->toBe('terlambat');
});
