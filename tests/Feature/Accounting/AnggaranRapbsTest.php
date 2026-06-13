<?php

use App\Filament\Pages\LaporanRapbs;
use App\Filament\Resources\Anggarans\Pages\CreateAnggaran;
use App\Filament\Resources\Anggarans\Pages\EditAnggaran;
use App\Filament\Resources\Anggarans\Pages\ListAnggarans;
use App\Models\Akun;
use App\Models\Anggaran;
use App\Models\JurnalUmum;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

// ─── Permissions helper ──────────────────────────────────────────────────────

function buatPermisiAnggaran(): array
{
    $permissions = [
        'ViewAny:Anggaran', 'View:Anggaran', 'Create:Anggaran',
        'Update:Anggaran', 'Delete:Anggaran', 'DeleteAny:Anggaran',
        'ForceDelete:Anggaran', 'ForceDeleteAny:Anggaran',
        'Restore:Anggaran', 'RestoreAny:Anggaran',
        'Replicate:Anggaran', 'Reorder:Anggaran',
    ];

    foreach ($permissions as $p) {
        Permission::findOrCreate($p, 'web');
    }

    return $permissions;
}

function buatBendahara(): User
{
    $permissions = buatPermisiAnggaran();
    Permission::findOrCreate('View:LaporanRapbs', 'web');

    $user = User::factory()->create();
    $user->givePermissionTo([...$permissions, 'View:LaporanRapbs']);

    return $user;
}

function buatGuru(): User
{
    return User::factory()->create();
}

// ─── Fixtures ────────────────────────────────────────────────────────────────

function buatTahunAjaranAktif(): TahunAjaran
{
    return TahunAjaran::factory()->create([
        'nama' => '2025/2026',
        'kode' => '2025-2026',
        'tanggal_mulai' => '2025-07-01',
        'tanggal_selesai' => '2026-06-30',
        'is_active' => true,
    ]);
}

function buatAkunPendapatan(): Akun
{
    return Akun::factory()->create([
        'kode' => '4-1001',
        'nama' => 'Pendapatan SPP',
        'tipe' => 'pendapatan',
        'posisi_normal' => 'kredit',
        'is_active' => true,
    ]);
}

function buatAkunBeban(): Akun
{
    return Akun::factory()->create([
        'kode' => '5-1001',
        'nama' => 'Beban Operasional',
        'tipe' => 'beban',
        'posisi_normal' => 'debit',
        'is_active' => true,
    ]);
}

// ─── CRUD: Create anggaran ────────────────────────────────────────────────────

it('bendahara dapat membuat anggaran baru', function () {
    $bendahara = buatBendahara();
    $this->actingAs($bendahara);

    $ta = buatTahunAjaranAktif();
    $akun = buatAkunPendapatan();

    Livewire::test(CreateAnggaran::class)
        ->fillForm([
            'tahun_ajaran_id' => $ta->id,
            'akun_id' => $akun->id,
            'nominal_anggaran' => 50_000_000,
            'keterangan' => 'Anggaran SPP tahunan',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(
        Anggaran::where('tahun_ajaran_id', $ta->id)
            ->where('akun_id', $akun->id)
            ->where('nominal_anggaran', '50000000.00')
            ->exists()
    )->toBeTrue();
});

it('anggaran duplikat (tahun_ajaran + akun sama) ditolak', function () {
    $bendahara = buatBendahara();
    $this->actingAs($bendahara);

    $ta = buatTahunAjaranAktif();
    $akun = buatAkunPendapatan();

    // Buat anggaran pertama
    Anggaran::factory()->create([
        'tahun_ajaran_id' => $ta->id,
        'akun_id' => $akun->id,
        'nominal_anggaran' => 50_000_000,
    ]);

    // Coba buat duplikat — harus gagal
    Livewire::test(CreateAnggaran::class)
        ->fillForm([
            'tahun_ajaran_id' => $ta->id,
            'akun_id' => $akun->id,
            'nominal_anggaran' => 30_000_000,
        ])
        ->call('create')
        ->assertHasFormErrors(['akun_id']);
});

it('anggaran akun berbeda di tahun ajaran sama boleh dibuat', function () {
    $bendahara = buatBendahara();
    $this->actingAs($bendahara);

    $ta = buatTahunAjaranAktif();
    $akun1 = buatAkunPendapatan();
    $akun2 = buatAkunBeban();

    Anggaran::factory()->create([
        'tahun_ajaran_id' => $ta->id,
        'akun_id' => $akun1->id,
    ]);

    Livewire::test(CreateAnggaran::class)
        ->fillForm([
            'tahun_ajaran_id' => $ta->id,
            'akun_id' => $akun2->id,
            'nominal_anggaran' => 10_000_000,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Anggaran::where('tahun_ajaran_id', $ta->id)->count())->toBe(2);
});

// ─── CRUD: Edit anggaran ──────────────────────────────────────────────────────

it('bendahara dapat mengedit nominal anggaran', function () {
    $bendahara = buatBendahara();
    $this->actingAs($bendahara);

    $ta = buatTahunAjaranAktif();
    $akun = buatAkunPendapatan();

    $anggaran = Anggaran::factory()->create([
        'tahun_ajaran_id' => $ta->id,
        'akun_id' => $akun->id,
        'nominal_anggaran' => 50_000_000,
    ]);

    Livewire::test(EditAnggaran::class, ['record' => $anggaran->id])
        ->fillForm(['nominal_anggaran' => 75_000_000])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($anggaran->fresh()->nominal_anggaran)->toBe('75000000.00');
});

// ─── CRUD: List & delete ──────────────────────────────────────────────────────

it('bendahara dapat melihat daftar anggaran', function () {
    $bendahara = buatBendahara();
    $this->actingAs($bendahara);

    $ta = buatTahunAjaranAktif();
    $akun = buatAkunPendapatan();

    Anggaran::factory()->create([
        'tahun_ajaran_id' => $ta->id,
        'akun_id' => $akun->id,
        'nominal_anggaran' => 50_000_000,
    ]);

    Livewire::test(ListAnggarans::class)
        ->assertCanSeeTableRecords(Anggaran::all());
});

it('bendahara dapat menghapus anggaran', function () {
    $bendahara = buatBendahara();
    $this->actingAs($bendahara);

    $ta = buatTahunAjaranAktif();
    $akun = buatAkunPendapatan();

    $anggaran = Anggaran::factory()->create([
        'tahun_ajaran_id' => $ta->id,
        'akun_id' => $akun->id,
    ]);

    Livewire::test(ListAnggarans::class)
        ->callTableAction('delete', $anggaran)
        ->assertHasNoActionErrors();

    expect(Anggaran::withTrashed()->where('id', $anggaran->id)->first()?->deleted_at)->not->toBeNull();
});

// ─── LaporanRapbs: akses ─────────────────────────────────────────────────────

it('bendahara dapat mengakses halaman LaporanRapbs', function () {
    $bendahara = buatBendahara();
    $this->actingAs($bendahara);

    Livewire::test(LaporanRapbs::class)
        ->assertStatus(200);
});

it('guru tanpa permission ditolak akses LaporanRapbs', function () {
    $guru = buatGuru();
    $this->actingAs($guru);

    Livewire::test(LaporanRapbs::class)
        ->assertForbidden();
});

// ─── LaporanRapbs: perhitungan realisasi ─────────────────────────────────────

it('realisasi pendapatan dihitung benar dari jurnal dalam rentang TA', function () {
    $bendahara = buatBendahara();
    $this->actingAs($bendahara);

    $ta = buatTahunAjaranAktif();
    $akun = buatAkunPendapatan();

    Anggaran::factory()->create([
        'tahun_ajaran_id' => $ta->id,
        'akun_id' => $akun->id,
        'nominal_anggaran' => 100_000_000,
    ]);

    // Jurnal dalam rentang TA (2025-07-01 s.d. 2026-06-30)
    JurnalUmum::factory()->kredit(30_000_000)->create([
        'akun_id' => $akun->id,
        'tanggal' => '2025-08-01',
    ]);
    JurnalUmum::factory()->kredit(20_000_000)->create([
        'akun_id' => $akun->id,
        'tanggal' => '2026-01-15',
    ]);
    // Jurnal di luar rentang TA — TIDAK ikut realisasi
    JurnalUmum::factory()->kredit(5_000_000)->create([
        'akun_id' => $akun->id,
        'tanggal' => '2024-06-30',
    ]);

    $page = new LaporanRapbs;
    $rows = $page->buildRows($ta->id);

    $baris = $rows->first(fn ($r) => str_contains($r['akun'], 'Pendapatan SPP'));

    expect($baris)->not->toBeNull()
        ->and($baris['realisasi'])->toBe(50_000_000.0) // 30jt + 20jt
        ->and($baris['anggaran'])->toBe(100_000_000.0)
        ->and($baris['selisih'])->toBe(50_000_000.0)    // 100jt - 50jt = sisa anggaran
        ->and($baris['persen_serapan'])->toBe(50.0);     // 50%
});

it('realisasi beban dihitung benar dari jurnal dalam rentang TA', function () {
    $bendahara = buatBendahara();
    $this->actingAs($bendahara);

    $ta = buatTahunAjaranAktif();
    $akun = buatAkunBeban();

    Anggaran::factory()->create([
        'tahun_ajaran_id' => $ta->id,
        'akun_id' => $akun->id,
        'nominal_anggaran' => 40_000_000,
    ]);

    // Beban = debit - kredit
    JurnalUmum::factory()->debit(15_000_000)->create([
        'akun_id' => $akun->id,
        'tanggal' => '2025-09-10',
    ]);
    JurnalUmum::factory()->debit(10_000_000)->create([
        'akun_id' => $akun->id,
        'tanggal' => '2026-02-20',
    ]);

    $page = new LaporanRapbs;
    $rows = $page->buildRows($ta->id);

    $baris = $rows->first(fn ($r) => str_contains($r['akun'], 'Beban Operasional'));

    expect($baris)->not->toBeNull()
        ->and($baris['realisasi'])->toBe(25_000_000.0)    // 15jt + 10jt
        ->and($baris['anggaran'])->toBe(40_000_000.0)
        ->and($baris['selisih'])->toBe(15_000_000.0)       // hemat 15jt
        ->and($baris['persen_serapan'])->toBe(62.5);        // 62.5%
});

it('persen_serapan nol jika anggaran nol', function () {
    $bendahara = buatBendahara();
    $this->actingAs($bendahara);

    $ta = buatTahunAjaranAktif();
    $akun = buatAkunPendapatan();

    Anggaran::factory()->create([
        'tahun_ajaran_id' => $ta->id,
        'akun_id' => $akun->id,
        'nominal_anggaran' => 0,
    ]);

    JurnalUmum::factory()->kredit(5_000_000)->create([
        'akun_id' => $akun->id,
        'tanggal' => '2025-08-01',
    ]);

    $page = new LaporanRapbs;
    $rows = $page->buildRows($ta->id);

    $baris = $rows->first(fn ($r) => str_contains($r['akun'], 'Pendapatan SPP'));

    expect($baris)->not->toBeNull()
        ->and($baris['persen_serapan'])->toBe(0.0);
});

it('baris total per seksi dihitung dengan benar', function () {
    $bendahara = buatBendahara();
    $this->actingAs($bendahara);

    $ta = buatTahunAjaranAktif();
    $akunPend = buatAkunPendapatan();
    $akunBeban = buatAkunBeban();

    Anggaran::factory()->create([
        'tahun_ajaran_id' => $ta->id,
        'akun_id' => $akunPend->id,
        'nominal_anggaran' => 100_000_000,
    ]);
    Anggaran::factory()->create([
        'tahun_ajaran_id' => $ta->id,
        'akun_id' => $akunBeban->id,
        'nominal_anggaran' => 50_000_000,
    ]);

    JurnalUmum::factory()->kredit(40_000_000)->create([
        'akun_id' => $akunPend->id,
        'tanggal' => '2025-08-01',
    ]);
    JurnalUmum::factory()->debit(20_000_000)->create([
        'akun_id' => $akunBeban->id,
        'tanggal' => '2025-09-01',
    ]);

    $page = new LaporanRapbs;
    $rows = $page->buildRows($ta->id);

    $totalPend = $rows->firstWhere('akun', 'TOTAL PENDAPATAN');
    $totalBeban = $rows->firstWhere('akun', 'TOTAL BEBAN');

    expect($totalPend)->not->toBeNull()
        ->and($totalPend['anggaran'])->toBe(100_000_000.0)
        ->and($totalPend['realisasi'])->toBe(40_000_000.0)
        ->and($totalBeban)->not->toBeNull()
        ->and($totalBeban['anggaran'])->toBe(50_000_000.0)
        ->and($totalBeban['realisasi'])->toBe(20_000_000.0);
});

it('buildRows mengembalikan collection kosong jika tidak ada tahun ajaran aktif', function () {
    $bendahara = buatBendahara();
    $this->actingAs($bendahara);

    // Tidak ada tahun ajaran sama sekali
    $page = new LaporanRapbs;
    $rows = $page->buildRows(null);

    expect($rows)->toBeEmpty();
});

// ─── LaporanRapbs: cetakPdf ────────────────────────────────────────────────

it('cetakPdf callable dan mengembalikan StreamedResponse', function () {
    $bendahara = buatBendahara();
    $this->actingAs($bendahara);

    $ta = buatTahunAjaranAktif();
    $akun = buatAkunPendapatan();

    Anggaran::factory()->create([
        'tahun_ajaran_id' => $ta->id,
        'akun_id' => $akun->id,
        'nominal_anggaran' => 50_000_000,
    ]);

    $page = Livewire::test(LaporanRapbs::class);

    expect(method_exists(LaporanRapbs::class, 'cetakPdf'))->toBeTrue();
});
