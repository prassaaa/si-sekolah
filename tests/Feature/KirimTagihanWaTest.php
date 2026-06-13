<?php

use App\Filament\Pages\KirimTagihan;
use App\Jobs\KirimTagihanWaJob;
use App\Models\NotifikasiTagihan;
use App\Models\Siswa;
use App\Models\TagihanSiswa;
use App\Models\User;
use App\Services\Wa\LogWaGateway;
use App\Services\Wa\WaGatewayContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'View:KirimTagihan']);
});

// --- Akses ---

it('pengguna dengan izin View:KirimTagihan bisa akses halaman', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('View:KirimTagihan');
    $this->actingAs($user);

    expect(KirimTagihan::canAccess())->toBeTrue();
});

it('pengguna tanpa izin tidak bisa akses halaman', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    expect(KirimTagihan::canAccess())->toBeFalse();
});

it('halaman KirimTagihan dapat dirender oleh pengguna yang berwenang', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('View:KirimTagihan');
    $this->actingAs($user);

    Livewire::test(KirimTagihan::class)->assertOk();
});

// --- Tabel: data yang muncul ---

it('tagihan belum bayar muncul di tabel', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('View:KirimTagihan');
    $this->actingAs($user);

    $tagihan = TagihanSiswa::factory()->belumBayar()->create([
        'tanggal_jatuh_tempo' => Carbon::now()->addDays(5),
    ]);

    Livewire::test(KirimTagihan::class)
        ->assertCanSeeTableRecords([$tagihan]);
});

it('tagihan lunas tidak muncul di tabel', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('View:KirimTagihan');
    $this->actingAs($user);

    $tagihan = TagihanSiswa::factory()->lunas()->create([
        'tanggal_jatuh_tempo' => Carbon::now()->subDays(10),
    ]);

    Livewire::test(KirimTagihan::class)
        ->assertCanNotSeeTableRecords([$tagihan]);
});

// --- Bulk action kirimWa: dispatch job ---

it('bulk action kirimWa membuat NotifikasiTagihan dan mendispatch KirimTagihanWaJob', function (): void {
    Bus::fake();

    $user = User::factory()->create();
    $user->givePermissionTo('View:KirimTagihan');
    $this->actingAs($user);

    $siswa = Siswa::factory()->create(['hp' => '081234567890']);

    $tagihan = TagihanSiswa::factory()->belumBayar()->create([
        'siswa_id' => $siswa->id,
        'tanggal_jatuh_tempo' => Carbon::now()->addDays(5),
        'sisa_tagihan' => 300000,
        'total_tagihan' => 300000,
    ]);

    Livewire::test(KirimTagihan::class)
        ->callTableBulkAction('kirimWa', [$tagihan]);

    expect(NotifikasiTagihan::query()->where('tagihan_siswa_id', $tagihan->id)->count())->toBe(1);

    Bus::assertDispatched(KirimTagihanWaJob::class);
});

it('bulk action kirimWa membuat notifikasi dengan data benar', function (): void {
    Bus::fake();

    $user = User::factory()->create();
    $user->givePermissionTo('View:KirimTagihan');
    $this->actingAs($user);

    $siswa = Siswa::factory()->create(['hp' => '081234567890']);

    $tagihan = TagihanSiswa::factory()->belumBayar()->create([
        'siswa_id' => $siswa->id,
        'tanggal_jatuh_tempo' => Carbon::now()->addDays(5),
        'sisa_tagihan' => 300000,
        'total_tagihan' => 300000,
    ]);

    Livewire::test(KirimTagihan::class)
        ->callTableBulkAction('kirimWa', [$tagihan]);

    $notifikasi = NotifikasiTagihan::query()->where('tagihan_siswa_id', $tagihan->id)->first();

    expect($notifikasi)->not->toBeNull()
        ->and($notifikasi->siswa_id)->toBe($siswa->id)
        ->and($notifikasi->tujuan_nomor)->toBe('6281234567890')
        ->and($notifikasi->status)->toBe('antri')
        ->and($notifikasi->driver)->toBe(config('wa.driver', 'log'));
});

it('bulk action tidak membuat notifikasi jika siswa tidak punya nomor HP', function (): void {
    Bus::fake();

    $user = User::factory()->create();
    $user->givePermissionTo('View:KirimTagihan');
    $this->actingAs($user);

    $siswa = Siswa::factory()->create([
        'hp' => null,
        'telepon' => null,
        'telepon_ayah' => null,
        'telepon_ibu' => null,
        'telepon_wali' => null,
    ]);

    $tagihan = TagihanSiswa::factory()->belumBayar()->create([
        'siswa_id' => $siswa->id,
        'tanggal_jatuh_tempo' => Carbon::now()->addDays(5),
    ]);

    Livewire::test(KirimTagihan::class)
        ->callTableBulkAction('kirimWa', [$tagihan]);

    expect(NotifikasiTagihan::query()->count())->toBe(0);
    Bus::assertNotDispatched(KirimTagihanWaJob::class);
});

// --- LogWaGateway ---

it('LogWaGateway mengembalikan status terkirim', function (): void {
    $gateway = new LogWaGateway;

    $result = $gateway->kirim('6281234567890', 'Pesan test');

    expect($result['status'])->toBe('terkirim')
        ->and($result['response'])->toContain('6281234567890');
});

it('LogWaGateway mengimplementasikan WaGatewayContract', function (): void {
    expect(new LogWaGateway)->toBeInstanceOf(WaGatewayContract::class);
});

// --- KirimTagihanWaJob ---

it('KirimTagihanWaJob memperbarui status notifikasi menjadi terkirim', function (): void {
    config(['queue.default' => 'sync', 'wa.driver' => 'log']);

    $user = User::factory()->create();
    $siswa = Siswa::factory()->create(['hp' => '081234567890']);
    $tagihan = TagihanSiswa::factory()->belumBayar()->create(['siswa_id' => $siswa->id]);

    $notifikasi = NotifikasiTagihan::query()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'siswa_id' => $siswa->id,
        'tujuan_nomor' => '6281234567890',
        'pesan' => 'Test pesan',
        'status' => 'antri',
        'driver' => 'log',
        'dikirim_oleh' => $user->id,
    ]);

    KirimTagihanWaJob::dispatch($notifikasi);

    $notifikasi->refresh();

    expect($notifikasi->status)->toBe('terkirim')
        ->and($notifikasi->sent_at)->not->toBeNull()
        ->and($notifikasi->response)->not->toBeNull();
});

// --- Format nomor ---

it('nomor 08xx diformat menjadi 628xx', function (): void {
    expect(KirimTagihan::formatNomorIndonesia('081234567890'))->toBe('6281234567890');
});

it('nomor 8xx (tanpa 0) diformat menjadi 628xx', function (): void {
    expect(KirimTagihan::formatNomorIndonesia('81234567890'))->toBe('6281234567890');
});

it('nomor yang sudah 62xx tidak berubah', function (): void {
    expect(KirimTagihan::formatNomorIndonesia('6281234567890'))->toBe('6281234567890');
});

it('karakter non-digit dihapus sebelum format', function (): void {
    expect(KirimTagihan::formatNomorIndonesia('+62 812-3456-7890'))->toBe('6281234567890');
});

// --- Prioritas nomor tujuan ---

it('prioritas nomor tujuan: hp siswa diutamakan', function (): void {
    $siswa = Siswa::factory()->create([
        'hp' => '081111111111',
        'telepon_ayah' => '082222222222',
    ]);
    $tagihan = TagihanSiswa::factory()->belumBayar()->create(['siswa_id' => $siswa->id]);

    expect(KirimTagihan::resolveNomorWa($tagihan))->toBe('6281111111111');
});

it('fallback ke telepon_ayah jika hp kosong', function (): void {
    $siswa = Siswa::factory()->create([
        'hp' => null,
        'telepon_ayah' => '082222222222',
        'telepon_ibu' => '083333333333',
    ]);
    $tagihan = TagihanSiswa::factory()->belumBayar()->create(['siswa_id' => $siswa->id]);

    expect(KirimTagihan::resolveNomorWa($tagihan))->toBe('6282222222222');
});

it('fallback ke telepon_ibu jika hp dan telepon_ayah kosong', function (): void {
    $siswa = Siswa::factory()->create([
        'hp' => null,
        'telepon_ayah' => null,
        'telepon_ibu' => '083333333333',
    ]);
    $tagihan = TagihanSiswa::factory()->belumBayar()->create(['siswa_id' => $siswa->id]);

    expect(KirimTagihan::resolveNomorWa($tagihan))->toBe('6283333333333');
});

it('fallback ke telepon_wali jika semua kolom lain kosong', function (): void {
    $siswa = Siswa::factory()->create([
        'hp' => null,
        'telepon' => null,
        'telepon_ayah' => null,
        'telepon_ibu' => null,
        'telepon_wali' => '084444444444',
    ]);
    $tagihan = TagihanSiswa::factory()->belumBayar()->create(['siswa_id' => $siswa->id]);

    expect(KirimTagihan::resolveNomorWa($tagihan))->toBe('6284444444444');
});

// --- buildPesan ---

it('buildPesan mengandung nama siswa, nomor tagihan, sisa, dan jatuh tempo', function (): void {
    $siswa = Siswa::factory()->create(['nama' => 'Budi Santoso']);
    $tagihan = TagihanSiswa::factory()->belumBayar()->create([
        'siswa_id' => $siswa->id,
        'nomor_tagihan' => 'TGH-001',
        'sisa_tagihan' => 250000,
        'tanggal_jatuh_tempo' => Carbon::parse('2026-07-15'),
    ]);

    $pesan = KirimTagihan::buildPesan($tagihan);

    expect($pesan)
        ->toContain('Budi Santoso')
        ->toContain('TGH-001')
        ->toContain('250.000')
        ->toContain('2026');
});
