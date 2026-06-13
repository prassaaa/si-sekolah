<?php

use App\Filament\Resources\JurnalUmums\Pages\EditJurnalUmum;
use App\Filament\Resources\JurnalUmums\Pages\ListJurnalUmums;
use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\SarprasBarang;
use App\Models\SarprasKategori;
use App\Models\SarprasPengadaan;
use App\Models\SarprasPengadaanItem;
use App\Models\User;
use App\Policies\JurnalUmumPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permissions = [
        'ViewAny:JurnalUmum', 'View:JurnalUmum', 'Create:JurnalUmum',
        'Update:JurnalUmum', 'Delete:JurnalUmum', 'DeleteAny:JurnalUmum',
        'ForceDelete:JurnalUmum', 'ForceDeleteAny:JurnalUmum',
        'Restore:JurnalUmum', 'RestoreAny:JurnalUmum',
        'Replicate:JurnalUmum', 'Reorder:JurnalUmum',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    $this->actingAs($user);
});

// ─── Helper ─────────────────────────────────────────────────────────────────

function buatAkunKas(): Akun
{
    return Akun::factory()->aset()->create(['kode' => '1-1001', 'nama' => 'Kas', 'kategori' => 'lancar']);
}

function buatAkunPerlengkapan(): Akun
{
    return Akun::factory()->aset()->create(['kode' => '1-3001', 'nama' => 'Perlengkapan', 'kategori' => 'lancar']);
}

function buatAkunAsetTetap(): Akun
{
    return Akun::factory()->aset()->create(['kode' => '1-4001', 'nama' => 'Peralatan', 'kategori' => 'tetap']);
}

// ─── (a) Jurnal manual bisa diedit/dihapus ──────────────────────────────────

it('jurnal manual (jenis_referensi null) isAutoPosted() returns false', function () {
    $akun = Akun::factory()->aset()->create();
    $jurnal = JurnalUmum::factory()->create([
        'akun_id' => $akun->id,
        'jenis_referensi' => null,
        'referensi_id' => null,
    ]);

    expect($jurnal->isAutoPosted())->toBeFalse();
});

it('jurnal manual policy update returns true untuk user berizin', function () {
    $akun = Akun::factory()->aset()->create();
    $jurnal = JurnalUmum::factory()->create([
        'akun_id' => $akun->id,
        'jenis_referensi' => null,
        'referensi_id' => null,
    ]);

    $user = auth()->user();
    $policy = new JurnalUmumPolicy;

    expect($policy->update($user, $jurnal))->toBeTrue()
        ->and($policy->delete($user, $jurnal))->toBeTrue();
});

it('EditAction terlihat untuk jurnal manual di tabel', function () {
    $akun = Akun::factory()->aset()->create();
    $jurnal = JurnalUmum::factory()->create([
        'akun_id' => $akun->id,
        'jenis_referensi' => null,
        'referensi_id' => null,
    ]);

    Livewire::test(ListJurnalUmums::class)
        ->assertTableActionVisible('edit', $jurnal);
});

// ─── (b) Jurnal auto-posted tidak bisa diedit/dihapus ───────────────────────

it('jurnal auto-posted (jenis_referensi terisi) isAutoPosted() returns true', function () {
    $akun = Akun::factory()->aset()->create();
    $jurnal = JurnalUmum::factory()->create([
        'akun_id' => $akun->id,
        'jenis_referensi' => 'sarpras_pengadaan',
        'referensi_id' => 1,
    ]);

    expect($jurnal->isAutoPosted())->toBeTrue();
});

it('jurnal auto-posted policy update returns false', function () {
    $akun = Akun::factory()->aset()->create();
    $jurnal = JurnalUmum::factory()->create([
        'akun_id' => $akun->id,
        'jenis_referensi' => 'sarpras_pengadaan',
        'referensi_id' => 1,
    ]);

    $user = auth()->user();
    $policy = new JurnalUmumPolicy;

    expect($policy->update($user, $jurnal))->toBeFalse()
        ->and($policy->delete($user, $jurnal))->toBeFalse()
        ->and($policy->forceDelete($user, $jurnal))->toBeFalse();
});

it('EditAction tersembunyi untuk jurnal auto-posted di tabel', function () {
    $akun = Akun::factory()->aset()->create();
    $jurnal = JurnalUmum::factory()->create([
        'akun_id' => $akun->id,
        'jenis_referensi' => 'kas_masuk',
        'referensi_id' => 99,
    ]);

    Livewire::test(ListJurnalUmums::class)
        ->assertTableActionHidden('edit', $jurnal);
});

it('jurnal auto-posted tidak bisa dicentang di bulk select (checkIfRecordIsSelectableUsing)', function () {
    $akun = Akun::factory()->aset()->create();
    $manual = JurnalUmum::factory()->create([
        'akun_id' => $akun->id,
        'jenis_referensi' => null,
    ]);
    $autoPosted = JurnalUmum::factory()->create([
        'akun_id' => $akun->id,
        'jenis_referensi' => 'kas_keluar',
        'referensi_id' => 1,
    ]);

    $livewire = Livewire::test(ListJurnalUmums::class)->instance();
    $table = $livewire->getTable();

    expect($table->isRecordSelectable($manual))->toBeTrue()
        ->and($table->isRecordSelectable($autoPosted))->toBeFalse();
});

it('halaman edit jurnal auto-posted ditolak oleh policy (403)', function () {
    $akun = Akun::factory()->aset()->create();
    $jurnal = JurnalUmum::factory()->create([
        'akun_id' => $akun->id,
        'jenis_referensi' => 'sarpras_penyusutan',
        'referensi_id' => 5,
    ]);

    // Policy update() mengembalikan false untuk auto-posted → Filament menolak akses 403
    Livewire::test(EditJurnalUmum::class, ['record' => $jurnal->getKey()])
        ->assertForbidden();
});

// ─── (c) Delete pengadaan status diterima ditolak ───────────────────────────

it('menghapus pengadaan status diterima melempar ValidationException', function () {
    $pengadaan = SarprasPengadaan::factory()->diterima()->create();

    expect(fn () => $pengadaan->delete())
        ->toThrow(ValidationException::class);
});

it('pengadaan diterima yang gagal dihapus tetap ada di database', function () {
    $pengadaan = SarprasPengadaan::factory()->diterima()->create();

    try {
        $pengadaan->delete();
    } catch (ValidationException) {
        // diharapkan
    }

    expect(SarprasPengadaan::find($pengadaan->id))->not->toBeNull();
});

it('pengadaan berstatus draft bisa dihapus tanpa error', function () {
    $pengadaan = SarprasPengadaan::factory()->create(['status' => 'draft']);

    expect(fn () => $pengadaan->delete())->not->toThrow(ValidationException::class);
    expect(SarprasPengadaan::withTrashed()->find($pengadaan->id)?->deleted_at)->not->toBeNull();
});

it('delete pengadaan diterima tidak menghapus jurnal yang terbentuk', function () {
    buatAkunKas();
    buatAkunPerlengkapan();

    $kategori = SarprasKategori::factory()->create();
    $pengadaan = SarprasPengadaan::factory()->disetujui()->create();

    SarprasPengadaanItem::factory()->create([
        'sarpras_pengadaan_id' => $pengadaan->id,
        'sarpras_kategori_id' => $kategori->id,
        'nama_barang' => 'Spidol',
        'jumlah' => 5,
        'harga_satuan' => 10000,
        'subtotal' => '50000.00',
    ]);

    $pengadaan->recalculateTotal();
    $pengadaan->terima();

    $jumlahJurnal = JurnalUmum::where('jenis_referensi', 'sarpras_pengadaan')
        ->where('referensi_id', $pengadaan->id)
        ->count();
    expect($jumlahJurnal)->toBeGreaterThan(0);

    try {
        $pengadaan->delete();
    } catch (ValidationException) {
        // diharapkan
    }

    // Jurnal harus tetap utuh
    expect(
        JurnalUmum::where('jenis_referensi', 'sarpras_pengadaan')
            ->where('referensi_id', $pengadaan->id)
            ->count()
    )->toBe($jumlahJurnal);
});

// ─── (d) Item bahan → jurnal debit ke Perlengkapan, bukan Aset Tetap ────────

it('pengadaan item bahan → jurnal debit ke akun Perlengkapan bukan Aset Tetap', function () {
    $kasAkun = buatAkunKas();
    $perlengkapanAkun = buatAkunPerlengkapan();
    $asetTetapAkun = buatAkunAsetTetap();

    $kategori = SarprasKategori::factory()->create();
    $pengadaan = SarprasPengadaan::factory()->disetujui()->create();

    SarprasPengadaanItem::factory()->create([
        'sarpras_pengadaan_id' => $pengadaan->id,
        'sarpras_kategori_id' => $kategori->id,
        'nama_barang' => 'Spidol Whiteboard',
        'jumlah' => 10,
        'harga_satuan' => 5000,
        'subtotal' => '50000.00',
    ]);

    $pengadaan->recalculateTotal();
    $pengadaan->terima();

    // SarprasBarang yang terbentuk harus bertipe 'bahan' (hardcode di terima())
    $barang = SarprasBarang::query()->where('nama', 'Spidol Whiteboard')->first();
    expect($barang)->not->toBeNull()
        ->and($barang->tipe)->toBe('bahan');

    // Baris debit harus ke Perlengkapan, bukan Aset Tetap
    $debitRows = JurnalUmum::where('jenis_referensi', 'sarpras_pengadaan')
        ->where('referensi_id', $pengadaan->id)
        ->where('debit', '>', 0)
        ->get();

    $akuIdsDebit = $debitRows->pluck('akun_id')->unique()->values()->all();

    expect($akuIdsDebit)->toContain($perlengkapanAkun->id)
        ->and($akuIdsDebit)->not->toContain($asetTetapAkun->id);
});

it('jurnal pengadaan tetap balance: total debit = total kredit', function () {
    buatAkunKas();
    buatAkunPerlengkapan();

    $kategori = SarprasKategori::factory()->create();
    $pengadaan = SarprasPengadaan::factory()->disetujui()->create();

    SarprasPengadaanItem::factory()->create([
        'sarpras_pengadaan_id' => $pengadaan->id,
        'sarpras_kategori_id' => $kategori->id,
        'jumlah' => 3,
        'harga_satuan' => 100000,
        'subtotal' => '300000.00',
    ]);

    $pengadaan->recalculateTotal();
    $pengadaan->terima();

    $rows = JurnalUmum::where('jenis_referensi', 'sarpras_pengadaan')
        ->where('referensi_id', $pengadaan->id)
        ->get();

    $totalDebit = $rows->sum(fn ($r) => (float) $r->debit);
    $totalKredit = $rows->sum(fn ($r) => (float) $r->kredit);

    expect($totalDebit)->toBe($totalKredit)
        ->and($totalDebit)->toBeGreaterThan(0.0);
});

it('reversePengadaan() tetap berjalan normal meski guard delete aktif pada model', function () {
    buatAkunKas();
    buatAkunPerlengkapan();

    $kategori = SarprasKategori::factory()->create();
    $pengadaan = SarprasPengadaan::factory()->disetujui()->create();

    SarprasPengadaanItem::factory()->create([
        'sarpras_pengadaan_id' => $pengadaan->id,
        'sarpras_kategori_id' => $kategori->id,
        'jumlah' => 2,
        'harga_satuan' => 50000,
        'subtotal' => '100000.00',
    ]);

    $pengadaan->recalculateTotal();
    $pengadaan->terima();

    $jumlahSebelum = JurnalUmum::where('jenis_referensi', 'sarpras_pengadaan')
        ->where('referensi_id', $pengadaan->id)
        ->count();
    expect($jumlahSebelum)->toBeGreaterThan(0);

    // reversePengadaan() soft-delete baris jurnal — harus jalan tanpa error
    $pengadaan->reverseJurnal();

    $jumlahSesudah = JurnalUmum::where('jenis_referensi', 'sarpras_pengadaan')
        ->where('referensi_id', $pengadaan->id)
        ->count();

    expect($jumlahSesudah)->toBe(0);
});
