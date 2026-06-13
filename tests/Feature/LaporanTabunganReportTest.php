<?php

use App\Filament\Pages\LaporanTabungan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TabunganSiswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'View:LaporanTabungan']);
});

/**
 * Buat baris tabungan langsung via model. Penarikan butuh DB::transaction agar
 * lockForUpdate pada assertWithdrawalIsCovered aktif.
 */
function buatTabunganLaporan(Siswa $siswa, string $jenis, float $nominal, string $tanggal): TabunganSiswa
{
    return DB::transaction(fn () => TabunganSiswa::create([
        'siswa_id' => $siswa->id,
        'jenis' => $jenis,
        'nominal' => $nominal,
        'tanggal' => $tanggal,
    ]));
}

function userBolehLaporanTabungan(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo('View:LaporanTabungan');

    return $user;
}

/**
 * Ambil baris record satu siswa dari tabel halaman.
 *
 * @param  array<int, array<string, mixed>>  $rows
 * @return array<string, mixed>|null
 */
function recordSiswa(array $rows, string $nis): ?array
{
    foreach ($rows as $row) {
        if (($row['nis'] ?? null) === $nis) {
            return $row;
        }
    }

    return null;
}

// ─── (a1) Saldo per siswa kronologis walau ada transaksi backdated ───────────

it('(a) menampilkan saldo kronologis (getSaldoSiswa), bukan baris ber-id terbesar', function () {
    $siswa = Siswa::factory()->create(['nis' => 'SDA-001']);

    // Urutan INSERT (id naik) sengaja TIDAK kronologis:
    //  id1: setor 100.000 tanggal 2026-03-01
    //  id2: setor  50.000 tanggal 2026-01-01 (BACKDATED → id besar, tanggal lama)
    // Setelah recalculate kronologis (tanggal,id):
    //  2026-01-01 setor 50.000  → saldo 50.000   (baris ber-id TERBESAR)
    //  2026-03-01 setor 100.000 → saldo 150.000  (baris kronologis TERAKHIR)
    buatTabunganLaporan($siswa, 'setor', 100000, '2026-03-01');
    buatTabunganLaporan($siswa, 'setor', 50000, '2026-01-01');

    // Sumber kebenaran: saldo akhir kronologis = 150.000.
    expect(TabunganSiswa::getSaldoSiswa($siswa->id))->toBe(150000.0);

    // Baris ber-id terbesar (versi lama yang salah) hanya 50.000 → harus TIDAK dipakai.
    $rowIdTerbesar = TabunganSiswa::query()
        ->where('siswa_id', $siswa->id)
        ->orderByDesc('id')
        ->first();
    expect((float) $rowIdTerbesar->saldo)->toBe(50000.0);

    $this->actingAs(userBolehLaporanTabungan());

    // Kosongkan rentang tanggal agar seluruh transaksi (lintas bulan) tampil,
    // sehingga yang diuji murni cara penentuan saldo per baris.
    $component = Livewire::test(LaporanTabungan::class)
        ->set('tableFilters.tanggal.tanggal_mulai', null)
        ->set('tableFilters.tanggal.tanggal_selesai', null);
    $component->assertOk();

    $row = recordSiswa($component->instance()->rows, 'SDA-001');

    expect($row)->not->toBeNull();
    // Halaman harus menampilkan 150.000 (kronologis), bukan 50.000 (id terbesar).
    expect((float) $row['saldo'])->toBe(150000.0);
});

// ─── (a2) Total Saldo = seluruh siswa bersaldo, bukan hanya yang difilter ────

it('(a) Total Saldo menjumlah SEMUA siswa bersaldo, termasuk di luar rentang tanggal', function () {
    // Siswa A bertransaksi di Januari 2026 (di LUAR filter default bulan ini).
    $siswaA = Siswa::factory()->create(['nis' => 'TS-A']);
    buatTabunganLaporan($siswaA, 'setor', 200000, '2026-01-10');

    // Siswa B bertransaksi pada hari ini (DI DALAM filter default).
    $siswaB = Siswa::factory()->create(['nis' => 'TS-B']);
    buatTabunganLaporan($siswaB, 'setor', 300000, now()->toDateString());

    $this->actingAs(userBolehLaporanTabungan());

    // Filter default = startOfMonth..now → secara baris hanya menangkap siswa B,
    // tetapi Total Saldo (kewajiban) harus tetap mencakup siswa A juga.
    $component = Livewire::test(LaporanTabungan::class);
    $component->assertOk();

    $summary = $component->instance()->summary;

    // 200.000 (A, di luar rentang) + 300.000 (B) = 500.000.
    expect((float) $summary['total_saldo'])->toBe(500000.0);
});

it('(a) Total Saldo menghormati filter kelas namun tetap lintas tanggal', function () {
    $kelas = Kelas::factory()->create(['nama' => '7A']);

    $siswaDiKelas = Siswa::factory()->forKelas($kelas)->create(['nis' => 'K-IN']);
    buatTabunganLaporan($siswaDiKelas, 'setor', 400000, '2026-01-05');

    $siswaLain = Siswa::factory()->create(['nis' => 'K-OUT']);
    buatTabunganLaporan($siswaLain, 'setor', 999000, '2026-01-05');

    $this->actingAs(userBolehLaporanTabungan());

    $component = Livewire::test(LaporanTabungan::class)
        ->set('tableFilters.kelas.value', (string) $kelas->id);

    $component->assertOk();

    $summary = $component->instance()->summary;

    // Hanya siswa di kelas 7A yang dihitung (400.000), siswa lain dikecualikan.
    expect((float) $summary['total_saldo'])->toBe(400000.0);
});

// ─── (c) Aksi cetak PDF tersedia & callable ──────────────────────────────────

it('(c) memiliki header action cetakPdf yang callable dan mengembalikan unduhan', function () {
    $siswa = Siswa::factory()->create(['nis' => 'PDF-1']);
    buatTabunganLaporan($siswa, 'setor', 100000, now()->toDateString());

    $this->actingAs(userBolehLaporanTabungan());

    Livewire::test(LaporanTabungan::class)
        ->assertOk()
        ->assertActionExists('cetakPdf')
        ->callAction('cetakPdf')
        ->assertHasNoActionErrors()
        ->assertFileDownloaded();
});
