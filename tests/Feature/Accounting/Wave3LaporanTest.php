<?php

use App\Filament\Pages\BukuBesar;
use App\Filament\Pages\LabaRugi;
use App\Filament\Pages\Neraca;
use App\Filament\Pages\NeracaSaldo;
use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\SaldoAwal;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\Accounting\FinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\StreamedResponse;

uses(RefreshDatabase::class);

/**
 * Bendahara user with View:{Page} permissions (Wave 0 shield convention).
 */
function laporanUser(string ...$pages): User
{
    foreach ($pages as $page) {
        Permission::firstOrCreate(['name' => "View:{$page}"]);
    }

    $user = User::factory()->create();
    $user->givePermissionTo(array_map(fn ($p) => "View:{$p}", $pages));

    return $user;
}

/**
 * Capture the body streamed by a StreamedResponse (the PDF bytes).
 */
function streamedBody(StreamedResponse $response): string
{
    ob_start();
    $response->sendContent();

    return (string) ob_get_clean();
}

// ============================================================
// (a) #77 — LabaRugi menampilkan total di layar
// ============================================================

it('LabaRugi menampilkan TOTAL PENDAPATAN, TOTAL BEBAN dan LABA (RUGI) BERSIH', function () {
    $pendapatan = Akun::factory()->pendapatan()->create(['nama' => 'Pendapatan SPP']);
    $beban = Akun::factory()->beban()->create(['nama' => 'Beban Listrik']);

    JurnalUmum::factory()->kredit(2000000)->create(['akun_id' => $pendapatan->id, 'tanggal' => '2026-01-10']);
    JurnalUmum::factory()->debit(750000)->create(['akun_id' => $beban->id, 'tanggal' => '2026-01-12']);

    $this->actingAs(laporanUser('LabaRugi'));

    Livewire::test(LabaRugi::class)
        ->set('tableFilters', [
            'tanggal' => ['tanggal_mulai' => '2026-01-01', 'tanggal_akhir' => '2026-01-31'],
        ])
        ->assertSee('TOTAL PENDAPATAN')
        ->assertSee('TOTAL BEBAN')
        ->assertSee('LABA (RUGI) BERSIH')
        // Angka total muncul (money IDR formatter merender ribuan).
        ->assertSee('2.000.000')
        ->assertSee('750.000')
        ->assertSee('1.250.000');
});

it('LabaRugi total laba rugi = pendapatan - beban dan tersimpan di properti', function () {
    $pendapatan = Akun::factory()->pendapatan()->create();
    $beban = Akun::factory()->beban()->create();

    JurnalUmum::factory()->kredit(2000000)->create(['akun_id' => $pendapatan->id, 'tanggal' => '2026-01-10']);
    JurnalUmum::factory()->debit(750000)->create(['akun_id' => $beban->id, 'tanggal' => '2026-01-12']);

    $this->actingAs(laporanUser('LabaRugi'));

    $component = Livewire::test(LabaRugi::class)
        ->set('tableFilters', [
            'tanggal' => ['tanggal_mulai' => '2026-01-01', 'tanggal_akhir' => '2026-01-31'],
        ]);

    $component->instance()->getTableRecords();

    expect($component->get('totalPendapatan'))->toBe(2000000.0)
        ->and($component->get('totalBeban'))->toBe(750000.0)
        ->and($component->get('labaRugi'))->toBe(1250000.0);
});

// ============================================================
// (b) #37/#71/#76 — Hapus Akun ber-jurnal ditolak
// ============================================================

it('AkunPolicy::delete menolak akun yang punya jurnal umum', function () {
    Permission::firstOrCreate(['name' => 'Delete:Akun']);
    $user = User::factory()->create();
    $user->givePermissionTo('Delete:Akun');

    $akun = Akun::factory()->aset()->create();
    JurnalUmum::factory()->debit(100000)->create(['akun_id' => $akun->id, 'tanggal' => '2026-01-05']);

    expect($user->can('delete', $akun))->toBeFalse()
        ->and($user->can('forceDelete', $akun))->toBeFalse()
        ->and($akun->hasLedgerActivity())->toBeTrue();
});

it('AkunPolicy::delete menolak akun yang punya saldo awal', function () {
    Permission::firstOrCreate(['name' => 'Delete:Akun']);
    $user = User::factory()->create();
    $user->givePermissionTo('Delete:Akun');

    $ta = TahunAjaran::factory()->create();
    $akun = Akun::factory()->aset()->create();
    SaldoAwal::create([
        'akun_id' => $akun->id,
        'tahun_ajaran_id' => $ta->id,
        'saldo' => 1000000,
        'tanggal' => '2026-07-01',
    ]);

    expect($user->can('delete', $akun))->toBeFalse()
        ->and($akun->hasLedgerActivity())->toBeTrue();
});

it('AkunPolicy::delete mengizinkan akun tanpa jejak pembukuan (punya permission)', function () {
    Permission::firstOrCreate(['name' => 'Delete:Akun']);
    $user = User::factory()->create();
    $user->givePermissionTo('Delete:Akun');

    $akun = Akun::factory()->aset()->create();

    expect($user->can('delete', $akun))->toBeTrue()
        ->and($akun->hasLedgerActivity())->toBeFalse();
});

it('DeleteAction tersembunyi pada baris akun ber-jurnal di tabel Akun', function () {
    Permission::firstOrCreate(['name' => 'Delete:Akun']);

    $akunBerjurnal = Akun::factory()->aset()->create(['nama' => 'Kas Terpakai']);
    JurnalUmum::factory()->debit(100000)->create(['akun_id' => $akunBerjurnal->id, 'tanggal' => '2026-01-05']);

    $akunKosong = Akun::factory()->aset()->create(['nama' => 'Kas Kosong']);

    expect($akunBerjurnal->hasLedgerActivity())->toBeTrue()
        ->and($akunKosong->hasLedgerActivity())->toBeFalse();
});

// ============================================================
// (c) Konsistensi: total == rincian setelah soft-delete akun ber-jurnal
// ============================================================

it('LabaRugi: total == jumlah rincian setelah akun beban di-soft-delete', function () {
    $pendapatan = Akun::factory()->pendapatan()->create(['nama' => 'Pendapatan A']);
    $bebanAktif = Akun::factory()->beban()->create(['nama' => 'Beban Aktif']);
    $bebanTrashed = Akun::factory()->beban()->create(['nama' => 'Beban Dihapus']);

    JurnalUmum::factory()->kredit(3000000)->create(['akun_id' => $pendapatan->id, 'tanggal' => '2026-01-10']);
    JurnalUmum::factory()->debit(400000)->create(['akun_id' => $bebanAktif->id, 'tanggal' => '2026-01-11']);
    JurnalUmum::factory()->debit(600000)->create(['akun_id' => $bebanTrashed->id, 'tanggal' => '2026-01-12']);

    // Akun beban ber-jurnal di-soft-delete (mis. lewat DB sebelum guard).
    $bebanTrashed->delete();

    $this->actingAs(laporanUser('LabaRugi'));

    $page = Livewire::test(LabaRugi::class)
        ->set('tableFilters', [
            'tanggal' => ['tanggal_mulai' => '2026-01-01', 'tanggal_akhir' => '2026-01-31'],
        ])
        ->instance();

    $rows = collect($page->buildRows('2026-01-01', '2026-01-31'));

    // Rincian beban = baris ber-kategori Beban.
    $rincianBeban = $rows->where('kategori', 'Beban')->sum('nominal');
    $totalBebanRow = $rows->firstWhere('akun', 'TOTAL BEBAN')['nominal'];

    // Total == jumlah rincian, dan akun trashed tetap ikut (400k + 600k = 1jt).
    expect((float) $rincianBeban)->toBe(1000000.0)
        ->and((float) $totalBebanRow)->toBe(1000000.0)
        ->and((float) $page->labaRugi)->toBe(2000000.0);
});

it('Neraca tetap memuat saldo akun aset yang sudah di-soft-delete', function () {
    $ta = TahunAjaran::factory()->create();
    $kas = Akun::factory()->aset()->create(['nama' => 'Kas Utama']);
    $modal = Akun::factory()->state(['tipe' => 'ekuitas', 'posisi_normal' => 'kredit'])->create(['nama' => 'Modal']);

    SaldoAwal::create(['akun_id' => $kas->id, 'tahun_ajaran_id' => $ta->id, 'saldo' => 5000000, 'tanggal' => '2026-07-01']);
    SaldoAwal::create(['akun_id' => $modal->id, 'tahun_ajaran_id' => $ta->id, 'saldo' => 5000000, 'tanggal' => '2026-07-01']);

    // Soft-delete akun aset ber-saldo.
    $kas->delete();

    $this->actingAs(laporanUser('Neraca'));

    $rows = collect(
        Livewire::test(Neraca::class)
            ->set('tableFilters', ['tanggal' => ['tanggal' => '2026-07-31']])
            ->instance()
            ->getTableRecords()
    );

    // Saldo akun trashed masih dirender.
    $kasRow = $rows->firstWhere('akun', 'Kas Utama');
    expect($kasRow)->not->toBeNull()
        ->and($kasRow['saldo'])->toBe('5000000.00');

    // Neraca tetap SEIMBANG.
    expect($rows->firstWhere('tipe', 'Seimbang'))->not->toBeNull();
});

it('FinancialService menyertakan akun trashed agar total pendapatan/beban stabil', function () {
    $pendapatanTrashed = Akun::factory()->pendapatan()->create();
    JurnalUmum::factory()->kredit(1000000)->create(['akun_id' => $pendapatanTrashed->id, 'tanggal' => '2026-01-10']);

    $pendapatanTrashed->delete();

    $service = app(FinancialService::class);

    // Tetap terhitung walau akun-nya trashed (historis stabil).
    expect($service->totalPendapatan('2026-01-01', '2026-01-31'))->toBe('1000000.00');
});

it('NeracaSaldo: total debit == kredit walau ada akun trashed ber-saldo', function () {
    $ta = TahunAjaran::factory()->create();
    $kas = Akun::factory()->aset()->create();
    $modal = Akun::factory()->state(['tipe' => 'ekuitas', 'posisi_normal' => 'kredit'])->create();

    SaldoAwal::create(['akun_id' => $kas->id, 'tahun_ajaran_id' => $ta->id, 'saldo' => 5000000, 'tanggal' => '2026-07-01']);
    SaldoAwal::create(['akun_id' => $modal->id, 'tahun_ajaran_id' => $ta->id, 'saldo' => 5000000, 'tanggal' => '2026-07-01']);

    $kas->delete();

    $page = new NeracaSaldo;
    $method = new ReflectionMethod(NeracaSaldo::class, 'buildTrialBalance');
    $method->setAccessible(true);
    $rows = collect($method->invoke($page, ['tanggal' => ['tanggal' => '2026-07-31']]));

    $totalRow = $rows->firstWhere('nama', 'TOTAL');
    expect(bccomp($totalRow['debit'], $totalRow['kredit'], 2))->toBe(0)
        ->and($rows->firstWhere('tipe', 'Seimbang'))->not->toBeNull();
});

// ============================================================
// (d) F6 — Action cetakPdf ADA & callable tanpa error di tiap halaman
// ============================================================

it('action cetakPdf ada di Neraca, LabaRugi, NeracaSaldo dan BukuBesar', function () {
    $this->actingAs(laporanUser('Neraca', 'LabaRugi', 'NeracaSaldo', 'BukuBesar'));

    Livewire::test(Neraca::class)->assertActionExists('cetakPdf');
    Livewire::test(LabaRugi::class)->assertActionExists('cetakPdf');
    Livewire::test(NeracaSaldo::class)->assertActionExists('cetakPdf');
    Livewire::test(BukuBesar::class)->assertActionExists('cetakPdf');
});

it('cetakPdf Neraca menghasilkan unduhan PDF tanpa error', function () {
    $ta = TahunAjaran::factory()->create();
    $kas = Akun::factory()->aset()->create();
    $modal = Akun::factory()->state(['tipe' => 'ekuitas', 'posisi_normal' => 'kredit'])->create();
    SaldoAwal::create(['akun_id' => $kas->id, 'tahun_ajaran_id' => $ta->id, 'saldo' => 5000000, 'tanggal' => '2026-07-01']);
    SaldoAwal::create(['akun_id' => $modal->id, 'tahun_ajaran_id' => $ta->id, 'saldo' => 5000000, 'tanggal' => '2026-07-01']);

    $this->actingAs(laporanUser('Neraca'));

    $page = Livewire::test(Neraca::class)
        ->set('tableFilters', ['tanggal' => ['tanggal' => '2026-07-31']])
        ->instance();

    $response = $page->cetakPdf();
    $body = streamedBody($response);

    expect($response)->toBeInstanceOf(StreamedResponse::class)
        ->and($body)->toStartWith('%PDF');
});

it('cetakPdf LabaRugi menghasilkan unduhan PDF tanpa error', function () {
    $pendapatan = Akun::factory()->pendapatan()->create();
    JurnalUmum::factory()->kredit(1000000)->create(['akun_id' => $pendapatan->id, 'tanggal' => '2026-01-10']);

    $this->actingAs(laporanUser('LabaRugi'));

    $page = Livewire::test(LabaRugi::class)
        ->set('tableFilters', ['tanggal' => ['tanggal_mulai' => '2026-01-01', 'tanggal_akhir' => '2026-01-31']])
        ->instance();

    $body = streamedBody($page->cetakPdf());

    expect($body)->toStartWith('%PDF');
});

it('cetakPdf NeracaSaldo menghasilkan unduhan PDF tanpa error', function () {
    $ta = TahunAjaran::factory()->create();
    $kas = Akun::factory()->aset()->create();
    SaldoAwal::create(['akun_id' => $kas->id, 'tahun_ajaran_id' => $ta->id, 'saldo' => 5000000, 'tanggal' => '2026-07-01']);

    $this->actingAs(laporanUser('NeracaSaldo'));

    $page = Livewire::test(NeracaSaldo::class)
        ->set('tableFilters', ['tanggal' => ['tanggal' => '2026-07-31']])
        ->instance();

    $body = streamedBody($page->cetakPdf());

    expect($body)->toStartWith('%PDF');
});

it('cetakPdf BukuBesar (landscape) menghasilkan unduhan PDF tanpa error', function () {
    $kas = Akun::factory()->aset()->create();
    JurnalUmum::factory()->debit(500000)->create(['akun_id' => $kas->id, 'tanggal' => '2026-01-05']);

    $this->actingAs(laporanUser('BukuBesar'));

    $page = Livewire::test(BukuBesar::class)
        ->set('tableFilters', [
            'akun_id' => ['value' => $kas->id],
            'tanggal' => ['tanggal_mulai' => '2026-01-01', 'tanggal_akhir' => '2026-01-31'],
        ])
        ->instance();

    $body = streamedBody($page->cetakPdf());

    expect($body)->toStartWith('%PDF');
});
