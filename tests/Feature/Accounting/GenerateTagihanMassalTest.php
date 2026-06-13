<?php

use App\Filament\Resources\TagihanSiswas\Pages\ListTagihanSiswas;
use App\Models\JenisPembayaran;
use App\Models\Kelas;
use App\Models\PembayaranPaket;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\TagihanSiswa;
use App\Models\User;
use App\Services\Accounting\GeneratorTagihanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function generatorService(): GeneratorTagihanService
{
    return app(GeneratorTagihanService::class);
}

function jenisBulanan(int $nominal = 250000): JenisPembayaran
{
    return JenisPembayaran::factory()->bulanan()->create([
        'nominal' => $nominal,
        'tanggal_jatuh_tempo' => null,
    ]);
}

// ─── generateMassal ──────────────────────────────────────────────────────────

it('membuat satu tagihan untuk tiap siswa aktif', function () {
    $jenis = jenisBulanan();
    $semester = Semester::factory()->create();
    Siswa::factory()->count(3)->create();

    $hasil = generatorService()->generateMassal($jenis, $semester, null, 7, 2026);

    expect($hasil)->toBe(['dibuat' => 3, 'dilewati' => 0])
        ->and(TagihanSiswa::count())->toBe(3);

    $tagihan = TagihanSiswa::first();
    expect((float) $tagihan->nominal)->toBe(250000.0)
        ->and((float) $tagihan->total_tagihan)->toBe(250000.0)
        ->and((float) $tagihan->sisa_tagihan)->toBe(250000.0)
        ->and((float) $tagihan->total_terbayar)->toBe(0.0)
        ->and($tagihan->status)->toBe('belum_bayar')
        ->and($tagihan->periode_bulan)->toBe(7)
        ->and($tagihan->periode_tahun)->toBe(2026)
        ->and($tagihan->semester_id)->toBe($semester->id)
        ->and($tagihan->nomor_tagihan)->not->toBeNull();
});

it('idempoten: dijalankan dua kali tidak menggandakan tagihan', function () {
    $jenis = jenisBulanan();
    $semester = Semester::factory()->create();
    Siswa::factory()->count(4)->create();

    $pertama = generatorService()->generateMassal($jenis, $semester, null, 7, 2026);
    $kedua = generatorService()->generateMassal($jenis, $semester, null, 7, 2026);

    expect($pertama)->toBe(['dibuat' => 4, 'dilewati' => 0])
        ->and($kedua)->toBe(['dibuat' => 0, 'dilewati' => 4])
        ->and(TagihanSiswa::count())->toBe(4);
});

it('membuat tagihan terpisah untuk periode bulan berbeda', function () {
    $jenis = jenisBulanan();
    $semester = Semester::factory()->create();
    Siswa::factory()->count(2)->create();

    generatorService()->generateMassal($jenis, $semester, null, 7, 2026);
    $agustus = generatorService()->generateMassal($jenis, $semester, null, 8, 2026);

    expect($agustus)->toBe(['dibuat' => 2, 'dilewati' => 0])
        ->and(TagihanSiswa::count())->toBe(4);
});

it('melewati siswa non-aktif (is_active false maupun status bukan aktif)', function () {
    $jenis = jenisBulanan();
    $semester = Semester::factory()->create();

    Siswa::factory()->count(2)->create();
    Siswa::factory()->inactive()->create();
    Siswa::factory()->create(['status' => 'pindah']);

    $hasil = generatorService()->generateMassal($jenis, $semester, null, 7, 2026);

    expect($hasil)->toBe(['dibuat' => 2, 'dilewati' => 0])
        ->and(TagihanSiswa::count())->toBe(2);
});

it('memfilter siswa per kelas bila kelas_id diberikan', function () {
    $jenis = jenisBulanan();
    $semester = Semester::factory()->create();
    $kelasA = Kelas::factory()->create();
    $kelasB = Kelas::factory()->create();

    Siswa::factory()->count(3)->forKelas($kelasA)->create();
    Siswa::factory()->count(2)->forKelas($kelasB)->create();

    $hasil = generatorService()->generateMassal($jenis, $semester, $kelasA->id, 7, 2026);

    expect($hasil)->toBe(['dibuat' => 3, 'dilewati' => 0])
        ->and(TagihanSiswa::count())->toBe(3)
        ->and(TagihanSiswa::pluck('siswa_id')->all())
        ->each->toBeIn(Siswa::where('kelas_id', $kelasA->id)->pluck('id')->all());
});

it('memakai tanggal jatuh tempo jenis pembayaran bila tersedia', function () {
    $jenis = JenisPembayaran::factory()->bulanan()->create([
        'tanggal_jatuh_tempo' => '2026-07-10',
    ]);
    $semester = Semester::factory()->create();
    Siswa::factory()->create();

    generatorService()->generateMassal($jenis, $semester, null, 7, 2026);

    expect(TagihanSiswa::first()->tanggal_jatuh_tempo->toDateString())->toBe('2026-07-10');
});

it('jatuh tempo default ke akhir bulan periode bila jenis tidak punya jatuh tempo', function () {
    $jenis = jenisBulanan();
    $semester = Semester::factory()->create();
    Siswa::factory()->create();

    generatorService()->generateMassal($jenis, $semester, null, 2, 2026);

    expect(TagihanSiswa::first()->tanggal_jatuh_tempo->toDateString())->toBe('2026-02-28');
});

// ─── terapkanPaket ───────────────────────────────────────────────────────────

it('membuat satu tagihan per item paket dengan nominal dari pivot', function () {
    $semester = Semester::factory()->create();
    $siswa = Siswa::factory()->create();

    $jenisA = JenisPembayaran::factory()->create();
    $jenisB = JenisPembayaran::factory()->create();

    $paket = PembayaranPaket::factory()->create();
    $paket->jenisPembayarans()->attach([
        $jenisA->id => ['nominal' => 300000],
        $jenisB->id => ['nominal' => 150000],
    ]);

    $hasil = generatorService()->terapkanPaket($paket, $siswa, $semester);

    expect($hasil)->toBe(['dibuat' => 2, 'dilewati' => 0])
        ->and(TagihanSiswa::count())->toBe(2);

    $tagihanA = TagihanSiswa::where('jenis_pembayaran_id', $jenisA->id)->first();
    $tagihanB = TagihanSiswa::where('jenis_pembayaran_id', $jenisB->id)->first();

    expect((float) $tagihanA->total_tagihan)->toBe(300000.0)
        ->and((float) $tagihanB->total_tagihan)->toBe(150000.0)
        ->and($tagihanA->semester_id)->toBe($semester->id)
        ->and($tagihanA->periode_bulan)->toBeNull();
});

it('terapkanPaket idempoten per siswa, jenis, dan semester', function () {
    $semester = Semester::factory()->create();
    $siswa = Siswa::factory()->create();
    $jenis = JenisPembayaran::factory()->create();

    $paket = PembayaranPaket::factory()->create();
    $paket->jenisPembayarans()->attach([$jenis->id => ['nominal' => 200000]]);

    $pertama = generatorService()->terapkanPaket($paket, $siswa, $semester);
    $kedua = generatorService()->terapkanPaket($paket, $siswa, $semester);

    expect($pertama)->toBe(['dibuat' => 1, 'dilewati' => 0])
        ->and($kedua)->toBe(['dibuat' => 0, 'dilewati' => 1])
        ->and(TagihanSiswa::count())->toBe(1);
});

// ─── UI: header action ───────────────────────────────────────────────────────

function userTagihanCreate(): User
{
    foreach (['ViewAny:TagihanSiswa', 'Create:TagihanSiswa'] as $name) {
        Permission::findOrCreate($name, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo(['ViewAny:TagihanSiswa', 'Create:TagihanSiswa']);

    return $user;
}

it('aksi generate massal di halaman list membuat tagihan dan dapat dijalankan', function () {
    $this->actingAs(userTagihanCreate());

    $jenis = jenisBulanan();
    $semester = Semester::factory()->create();
    Siswa::factory()->count(2)->create();

    Livewire::test(ListTagihanSiswas::class)
        ->assertActionExists('generateMassal')
        ->callAction('generateMassal', [
            'jenis_pembayaran_id' => $jenis->id,
            'semester_id' => $semester->id,
            'kelas_id' => null,
            'bulan' => 7,
            'tahun' => 2026,
        ])
        ->assertHasNoActionErrors();

    expect(TagihanSiswa::count())->toBe(2);
});

it('aksi terapkan paket di halaman list membuat tagihan per item paket', function () {
    $this->actingAs(userTagihanCreate());

    $semester = Semester::factory()->create();
    $siswa = Siswa::factory()->create();
    $jenis = JenisPembayaran::factory()->create();
    $paket = PembayaranPaket::factory()->create();
    $paket->jenisPembayarans()->attach([$jenis->id => ['nominal' => 175000]]);

    Livewire::test(ListTagihanSiswas::class)
        ->assertActionExists('terapkanPaket')
        ->callAction('terapkanPaket', [
            'pembayaran_paket_id' => $paket->id,
            'siswa_id' => $siswa->id,
            'semester_id' => $semester->id,
        ])
        ->assertHasNoActionErrors();

    expect(TagihanSiswa::count())->toBe(1)
        ->and((float) TagihanSiswa::first()->total_tagihan)->toBe(175000.0);
});

it('menyembunyikan aksi generate massal tanpa permission Create:TagihanSiswa', function () {
    foreach (['ViewAny:TagihanSiswa'] as $name) {
        Permission::findOrCreate($name, 'web');
    }
    $user = User::factory()->create();
    $user->givePermissionTo('ViewAny:TagihanSiswa');
    $this->actingAs($user);

    Livewire::test(ListTagihanSiswas::class)
        ->assertActionHidden('generateMassal')
        ->assertActionHidden('terapkanPaket');
});
