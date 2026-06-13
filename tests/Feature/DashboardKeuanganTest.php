<?php

use App\Filament\Widgets\Laporan\RingkasanTunggakanWidget;
use App\Filament\Widgets\Laporan\SaldoKasBankWidget;
use App\Filament\Widgets\Laporan\TrenKeuanganChart;
use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TagihanSiswa;
use App\Models\User;
use App\Services\Accounting\FinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'View:DashboardKeuangan']);
    Role::firstOrCreate(['name' => 'kepala_sekolah']);
    Role::firstOrCreate(['name' => 'guru']);
});

// ===== GATE WIDGET canView() (dashboard utama, per role) =====

it('widget canView mengembalikan true untuk user dengan permission View:DashboardKeuangan', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('View:DashboardKeuangan');
    $this->actingAs($user);

    expect(SaldoKasBankWidget::canView())->toBeTrue()
        ->and(RingkasanTunggakanWidget::canView())->toBeTrue()
        ->and(TrenKeuanganChart::canView())->toBeTrue();
});

it('widget canView mengembalikan false untuk user tanpa permission keuangan', function () {
    $user = User::factory()->create();
    $user->assignRole('guru');
    $this->actingAs($user);

    expect(SaldoKasBankWidget::canView())->toBeFalse()
        ->and(RingkasanTunggakanWidget::canView())->toBeFalse()
        ->and(TrenKeuanganChart::canView())->toBeFalse();
});

it('widget keuangan dapat dirender (muncul di dashboard utama untuk user keuangan)', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('View:DashboardKeuangan');
    $this->actingAs($user);

    Livewire::test(SaldoKasBankWidget::class)->assertOk();
    Livewire::test(RingkasanTunggakanWidget::class)->assertOk();
    Livewire::test(TrenKeuanganChart::class)->assertOk();
});

// ===== SALDO KAS BANK =====

it('SaldoKasBankWidget menampilkan saldo akun kas yang sesuai', function () {
    /** Buat akun kas sesuai kode yang digunakan widget. */
    $kas = Akun::factory()->aset()->create([
        'kode' => '1-1001',
        'nama' => 'Kas',
        'posisi_normal' => 'debit',
        'tipe' => 'aset',
    ]);

    $bankBca = Akun::factory()->aset()->create([
        'kode' => '1-1002',
        'nama' => 'Bank BCA',
        'posisi_normal' => 'debit',
        'tipe' => 'aset',
    ]);

    /** Buat entri jurnal debit untuk akun Kas sebesar 5.000.000. */
    JurnalUmum::factory()->debit(5000000)->create([
        'akun_id' => $kas->id,
        'tanggal' => now()->subDays(5),
    ]);

    /** Buat entri jurnal debit untuk Bank BCA sebesar 10.000.000. */
    JurnalUmum::factory()->debit(10000000)->create([
        'akun_id' => $bankBca->id,
        'tanggal' => now()->subDays(3),
    ]);

    $financial = app(FinancialService::class);
    $saldo = $financial->saldoPerAkun([$kas->id, $bankBca->id], now()->toDateString());

    expect((float) ($saldo[$kas->id] ?? 0))->toBe(5000000.0)
        ->and((float) ($saldo[$bankBca->id] ?? 0))->toBe(10000000.0);
});

// ===== TREN KEUANGAN =====

it('TrenKeuanganChart menghitung pendapatan dan beban dengan benar', function () {
    $pendapatanAkun = Akun::factory()->pendapatan()->create();
    $bebanAkun = Akun::factory()->beban()->create();

    $bulanIni = now()->startOfMonth();

    /** Buat pendapatan 2.000.000 bulan ini. */
    JurnalUmum::factory()->kredit(2000000)->create([
        'akun_id' => $pendapatanAkun->id,
        'tanggal' => $bulanIni->copy()->addDays(2),
    ]);

    /** Buat beban 800.000 bulan ini. */
    JurnalUmum::factory()->debit(800000)->create([
        'akun_id' => $bebanAkun->id,
        'tanggal' => $bulanIni->copy()->addDays(5),
    ]);

    $financial = app(FinancialService::class);

    $pendapatan = $financial->totalPendapatan($bulanIni, now()->endOfMonth());
    $beban = $financial->totalBeban($bulanIni, now()->endOfMonth());
    $netIncome = $financial->netIncome($bulanIni, now()->endOfMonth());

    expect((float) $pendapatan)->toBe(2000000.0)
        ->and((float) $beban)->toBe(800000.0)
        ->and((float) $netIncome)->toBe(1200000.0);
});

// ===== RINGKASAN TUNGGAKAN =====

it('RingkasanTunggakanWidget menghitung total tunggakan dan jumlah siswa menunggak', function () {
    /** Buat kelas dan siswa. */
    $kelas = Kelas::factory()->create();

    $siswa1 = Siswa::factory()->create(['kelas_id' => $kelas->id]);
    $siswa2 = Siswa::factory()->create(['kelas_id' => $kelas->id]);
    $siswa3 = Siswa::factory()->create(['kelas_id' => $kelas->id]);

    /** Tagihan belum_bayar untuk siswa1 sebesar 500.000. */
    TagihanSiswa::factory()->create([
        'siswa_id' => $siswa1->id,
        'status' => 'belum_bayar',
        'sisa_tagihan' => 500000,
    ]);

    /** Tagihan sebagian untuk siswa2 sebesar 300.000. */
    TagihanSiswa::factory()->create([
        'siswa_id' => $siswa2->id,
        'status' => 'sebagian',
        'sisa_tagihan' => 300000,
    ]);

    /** Tagihan lunas tidak masuk tunggakan. */
    TagihanSiswa::factory()->create([
        'siswa_id' => $siswa3->id,
        'status' => 'lunas',
        'sisa_tagihan' => 0,
    ]);

    /** Tagihan batal tidak masuk tunggakan. */
    TagihanSiswa::factory()->create([
        'siswa_id' => $siswa3->id,
        'status' => 'batal',
        'sisa_tagihan' => 200000,
    ]);

    $totalTunggakan = TagihanSiswa::belumLunas()->sum('sisa_tagihan');
    $jumlahSiswa = TagihanSiswa::belumLunas()->distinct('siswa_id')->count('siswa_id');

    expect((float) $totalTunggakan)->toBe(800000.0)
        ->and($jumlahSiswa)->toBe(2);
});

it('RingkasanTunggakanWidget mengecualikan tagihan batal dari total tunggakan', function () {
    $kelas = Kelas::factory()->create();
    $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

    /** Hanya tagihan batal, tidak boleh dihitung. */
    TagihanSiswa::factory()->create([
        'siswa_id' => $siswa->id,
        'status' => 'batal',
        'sisa_tagihan' => 999999,
    ]);

    $totalTunggakan = TagihanSiswa::belumLunas()->sum('sisa_tagihan');

    expect((float) $totalTunggakan)->toBe(0.0);
});
