<?php

use App\Filament\Resources\PeriodeAkuntansis\Pages\ListPeriodeAkuntansis;
use App\Models\Akun;
use App\Models\KasMasuk;
use App\Models\PeriodeAkuntansi;
use App\Models\User;
use App\Policies\PeriodeAkuntansiPolicy;
use App\Services\Accounting\PeriodeGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

// ─── Helper ─────────────────────────────────────────────────────────────────

/**
 * Buat akun Kas tunai default (kode 1-1001) + akun lawan (pendapatan).
 *
 * @return array{kas: Akun, lawan: Akun}
 */
function buatAkunTutupBuku(): array
{
    return [
        'kas' => Akun::factory()->aset()->create(['kode' => '1-1001', 'nama' => 'Kas', 'kategori' => 'lancar']),
        'lawan' => Akun::factory()->pendapatan()->create(['kode' => '4-1001', 'nama' => 'Pendapatan SPP']),
    ];
}

/**
 * Buat KasMasuk langsung (tanpa lewat Filament) untuk menguji guard model.
 */
function buatKasMasukTutupBuku(string $tanggal, array $akun): KasMasuk
{
    return KasMasuk::create([
        'akun_id' => $akun['lawan']->id,
        'kas_akun_id' => $akun['kas']->id,
        'tanggal' => $tanggal,
        'nominal' => '100000.00',
        'sumber' => 'Tes',
        'keterangan' => 'Tes tutup buku',
        'user_id' => User::factory()->create()->id,
    ]);
}

function tutupPeriode(int $tahun, int $bulan): PeriodeAkuntansi
{
    return PeriodeAkuntansi::factory()->closed()->periode($tahun, $bulan)->create();
}

// ─── (a) Membuat transaksi pada bulan tertutup ditolak ───────────────────────

it('(a) menolak membuat KasMasuk bertanggal pada periode yang sudah ditutup', function () {
    $akun = buatAkunTutupBuku();
    tutupPeriode(2026, 7);

    expect(fn () => buatKasMasukTutupBuku('2026-07-15', $akun))
        ->toThrow(ValidationException::class);

    expect(KasMasuk::count())->toBe(0);
});

it('pesan ValidationException menyebutkan periode bulan/tahun yang ditutup', function () {
    tutupPeriode(2026, 7);

    try {
        app(PeriodeGuard::class)->assertOpen('2026-07-15');
        $this->fail('Seharusnya melempar ValidationException.');
    } catch (ValidationException $e) {
        expect($e->errors()['tanggal'][0])
            ->toContain('Periode 7/2026 sudah ditutup');
    }
});

// ─── (b) Membuat transaksi pada bulan terbuka sukses ─────────────────────────

it('(b) mengizinkan membuat KasMasuk bertanggal pada periode yang masih terbuka', function () {
    $akun = buatAkunTutupBuku();
    tutupPeriode(2026, 7);

    // Bulan Agustus 2026 belum ditutup → harus sukses.
    $kas = buatKasMasukTutupBuku('2026-08-15', $akun);

    expect($kas->exists)->toBeTrue()
        ->and(KasMasuk::count())->toBe(1);
});

it('mengizinkan transaksi saat tidak ada periode tertutup sama sekali', function () {
    $akun = buatAkunTutupBuku();

    $kas = buatKasMasukTutupBuku('2026-07-15', $akun);

    expect($kas->exists)->toBeTrue();
});

// ─── (c) Edit / hapus transaksi pada periode tertutup ditolak ────────────────

it('(c) menolak mengedit KasMasuk pada periode yang ditutup setelah pembuatan', function () {
    $akun = buatAkunTutupBuku();
    $kas = buatKasMasukTutupBuku('2026-07-15', $akun);

    // Tutup periode setelah transaksi sudah ada.
    tutupPeriode(2026, 7);

    $kas->nominal = '250000.00';

    expect(fn () => $kas->save())->toThrow(ValidationException::class);

    expect((string) KasMasuk::find($kas->id)->nominal)->toBe('100000.00');
});

it('(c) menolak menghapus KasMasuk pada periode yang ditutup', function () {
    $akun = buatAkunTutupBuku();
    $kas = buatKasMasukTutupBuku('2026-07-15', $akun);

    tutupPeriode(2026, 7);

    expect(fn () => $kas->delete())->toThrow(ValidationException::class);

    expect(KasMasuk::find($kas->id))->not->toBeNull();
});

it('mengizinkan edit transaksi pada periode lain yang tetap terbuka', function () {
    $akun = buatAkunTutupBuku();
    $kas = buatKasMasukTutupBuku('2026-08-15', $akun);

    // Tutup Juli, transaksi ada di Agustus → edit harus tetap boleh.
    tutupPeriode(2026, 7);

    $kas->nominal = '300000.00';
    $kas->save();

    expect((string) KasMasuk::find($kas->id)->nominal)->toBe('300000.00');
});

// ─── (d) Reopen: non-super_admin ditolak, super_admin boleh ──────────────────

it('(d) reopen ditolak untuk pengguna non-super_admin', function () {
    Permission::findOrCreate('Update:PeriodeAkuntansi', 'web');

    $user = User::factory()->create();
    $user->givePermissionTo('Update:PeriodeAkuntansi');

    $periode = PeriodeAkuntansi::factory()->closed()->periode(2026, 7)->create();
    $policy = new PeriodeAkuntansiPolicy;

    expect($policy->reopen($user, $periode))->toBeFalse()
        ->and($policy->update($user, $periode))->toBeFalse();
});

it('(d) reopen diizinkan untuk super_admin', function () {
    Role::findOrCreate('super_admin', 'web');

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $periode = PeriodeAkuntansi::factory()->closed()->periode(2026, 7)->create();
    $policy = new PeriodeAkuntansiPolicy;

    expect($policy->reopen($admin, $periode))->toBeTrue()
        ->and($policy->update($admin, $periode))->toBeTrue();
});

it('tutup diizinkan untuk pengguna berizin Update saat periode masih terbuka', function () {
    Permission::findOrCreate('Update:PeriodeAkuntansi', 'web');

    $user = User::factory()->create();
    $user->givePermissionTo('Update:PeriodeAkuntansi');

    $periode = PeriodeAkuntansi::factory()->periode(2026, 7)->create();
    $policy = new PeriodeAkuntansiPolicy;

    expect($policy->tutup($user, $periode))->toBeTrue();
});

it('tutup ditolak bila periode sudah tertutup', function () {
    Permission::findOrCreate('Update:PeriodeAkuntansi', 'web');

    $user = User::factory()->create();
    $user->givePermissionTo('Update:PeriodeAkuntansi');

    $periode = PeriodeAkuntansi::factory()->closed()->periode(2026, 7)->create();
    $policy = new PeriodeAkuntansiPolicy;

    expect($policy->tutup($user, $periode))->toBeFalse();
});

// ─── (e) Setelah reopen, transaksi bisa dibuat/diubah lagi ───────────────────

it('(e) setelah periode dibuka kembali, transaksi pada periode itu bisa dibuat lagi', function () {
    $akun = buatAkunTutupBuku();
    $periode = tutupPeriode(2026, 7);

    // Saat tertutup → ditolak.
    expect(fn () => buatKasMasukTutupBuku('2026-07-15', $akun))
        ->toThrow(ValidationException::class);

    // Buka kembali.
    $periode->update(['status' => 'open', 'closed_by' => null, 'closed_at' => null]);

    // Sekarang harus sukses.
    $kas = buatKasMasukTutupBuku('2026-07-15', $akun);

    expect($kas->exists)->toBeTrue()
        ->and(KasMasuk::count())->toBe(1);
});

// ─── Model helper isClosed() ─────────────────────────────────────────────────

it('PeriodeAkuntansi::isClosed mengembalikan true hanya untuk periode tertutup', function () {
    tutupPeriode(2026, 7);
    PeriodeAkuntansi::factory()->periode(2026, 8)->create();

    expect(PeriodeAkuntansi::isClosed(2026, 7))->toBeTrue()
        ->and(PeriodeAkuntansi::isClosed(2026, 8))->toBeFalse()
        ->and(PeriodeAkuntansi::isClosed(2026, 9))->toBeFalse();
});

it('PeriodeGuard melewati tanggal null tanpa error', function () {
    tutupPeriode(2026, 7);

    expect(fn () => app(PeriodeGuard::class)->assertOpen(null))
        ->not->toThrow(ValidationException::class);
});

// ─── UI resource: action Tutup & Buka di daftar ──────────────────────────────

it('action Tutup di tabel menutup periode dan mengisi penutup', function () {
    Permission::findOrCreate('ViewAny:PeriodeAkuntansi', 'web');
    Permission::findOrCreate('Update:PeriodeAkuntansi', 'web');

    $user = User::factory()->create();
    $user->givePermissionTo(['ViewAny:PeriodeAkuntansi', 'Update:PeriodeAkuntansi']);
    $this->actingAs($user);

    $periode = PeriodeAkuntansi::factory()->periode(2026, 7)->create();

    Livewire::test(ListPeriodeAkuntansis::class)
        ->callTableAction('tutup', $periode, data: ['keterangan' => 'Laporan diserahkan'])
        ->assertHasNoTableActionErrors();

    $periode->refresh();

    expect($periode->status)->toBe('closed')
        ->and($periode->closed_by)->toBe($user->id)
        ->and($periode->closed_at)->not->toBeNull();
});

it('action Buka Kembali tersembunyi untuk non-super_admin', function () {
    Permission::findOrCreate('ViewAny:PeriodeAkuntansi', 'web');
    Permission::findOrCreate('Update:PeriodeAkuntansi', 'web');

    $user = User::factory()->create();
    $user->givePermissionTo(['ViewAny:PeriodeAkuntansi', 'Update:PeriodeAkuntansi']);
    $this->actingAs($user);

    $periode = PeriodeAkuntansi::factory()->closed()->periode(2026, 7)->create();

    Livewire::test(ListPeriodeAkuntansis::class)
        ->assertTableActionHidden('buka', $periode);
});

it('action Buka Kembali terlihat & berfungsi untuk super_admin', function () {
    Permission::findOrCreate('ViewAny:PeriodeAkuntansi', 'web');
    Role::findOrCreate('super_admin', 'web');

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $admin->givePermissionTo(Permission::findOrCreate('ViewAny:PeriodeAkuntansi', 'web'));
    $this->actingAs($admin);

    $periode = PeriodeAkuntansi::factory()->closed()->periode(2026, 7)->create();

    Livewire::test(ListPeriodeAkuntansis::class)
        ->assertTableActionVisible('buka', $periode)
        ->callTableAction('buka', $periode)
        ->assertHasNoTableActionErrors();

    $periode->refresh();

    expect($periode->status)->toBe('open')
        ->and($periode->closed_by)->toBeNull()
        ->and($periode->closed_at)->toBeNull();
});
