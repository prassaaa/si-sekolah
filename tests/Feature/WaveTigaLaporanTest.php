<?php

use App\Filament\Pages\ArusKasBank;
use App\Filament\Pages\LaporanKeuangan;
use App\Filament\Pages\LaporanPembayaran;
use App\Filament\Pages\LaporanPembayaranPerKelas;
use App\Filament\Pages\LaporanPembayaranPerTanggal;
use App\Filament\Pages\LaporanTagihanSiswa;
use App\Filament\Pages\LaporanUnitPos;
use App\Filament\Widgets\Laporan\LaporanTagihanSiswaStats;
use App\Models\Akun;
use App\Models\JenisPembayaran;
use App\Models\KasKeluar;
use App\Models\KasMasuk;
use App\Models\Kelas;
use App\Models\KenaikanKelas;
use App\Models\Pembayaran;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\TagihanSiswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

/**
 * Beri user akses ke sebuah halaman ber-HasPageShield (pola Wave 0:
 * permission "View:{Page}" lalu givePermissionTo).
 */
function aktorDenganAkses(string $pageClass): User
{
    $permission = 'View:'.class_basename($pageClass);
    Permission::firstOrCreate(['name' => $permission]);

    $user = User::factory()->create();
    $user->givePermissionTo($permission);
    test()->actingAs($user);

    return $user;
}

/**
 * Buat akun kas (Kas tunai) + akun lawan pendapatan & beban untuk uji arus kas.
 *
 * @return array{kas: Akun, pendapatan: Akun, beban: Akun}
 */
function akunArusKas(): array
{
    return [
        'kas' => Akun::factory()->create([
            'kode' => '1-1001',
            'nama' => 'Kas',
            'tipe' => 'aset',
            'kategori' => 'lancar',
            'posisi_normal' => 'debit',
        ]),
        'pendapatan' => Akun::factory()->create([
            'kode' => '4-1001',
            'nama' => 'Pendapatan SPP',
            'tipe' => 'pendapatan',
            'kategori' => 'operasional',
            'posisi_normal' => 'kredit',
        ]),
        'beban' => Akun::factory()->create([
            'kode' => '5-1001',
            'nama' => 'Beban Operasional',
            'tipe' => 'beban',
            'kategori' => 'operasional',
            'posisi_normal' => 'debit',
        ]),
    ];
}

it('arus kas menyajikan saldo awal, total, dan saldo akhir yang benar', function () {
    $akun = akunArusKas();
    aktorDenganAkses(ArusKasBank::class);

    // Kas masuk SEBELUM periode -> membentuk saldo kas awal periode (1.000.000).
    KasMasuk::create([
        'akun_id' => $akun['pendapatan']->id,
        'kas_akun_id' => $akun['kas']->id,
        'tanggal' => '2026-01-10',
        'nominal' => 1000000,
        'sumber' => 'Saldo awal kas',
    ]);

    // Penerimaan dalam periode (500.000).
    KasMasuk::create([
        'akun_id' => $akun['pendapatan']->id,
        'kas_akun_id' => $akun['kas']->id,
        'tanggal' => '2026-02-05',
        'nominal' => 500000,
        'sumber' => 'Pembayaran SPP',
    ]);

    // Pengeluaran dalam periode (200.000).
    KasKeluar::create([
        'akun_id' => $akun['beban']->id,
        'kas_akun_id' => $akun['kas']->id,
        'tanggal' => '2026-02-10',
        'nominal' => 200000,
        'penerima' => 'Toko ATK',
    ]);

    $component = Livewire::test(ArusKasBank::class)
        ->set('tableFilters', [
            'tanggal' => [
                'tanggal_mulai' => '2026-02-01',
                'tanggal_akhir' => '2026-02-28',
            ],
        ]);

    $records = $component->instance()->getTableRecords();
    $ringkasan = $component->get('ringkasan');

    // Hanya transaksi dalam periode yang tampil sebagai baris (2 baris).
    expect($records)->toHaveCount(2)
        ->and((float) $ringkasan['saldo_awal'])->toBe(1000000.0)
        ->and((float) $ringkasan['total_penerimaan'])->toBe(500000.0)
        ->and((float) $ringkasan['total_pengeluaran'])->toBe(200000.0)
        ->and((float) $ringkasan['saldo_akhir'])->toBe(1300000.0);
});

it('arus kas mengklasifikasikan transaksi ke aktivitas operasi', function () {
    $akun = akunArusKas();
    aktorDenganAkses(ArusKasBank::class);

    KasMasuk::create([
        'akun_id' => $akun['pendapatan']->id,
        'kas_akun_id' => $akun['kas']->id,
        'tanggal' => '2026-02-05',
        'nominal' => 500000,
        'sumber' => 'Pembayaran SPP',
    ]);

    $component = Livewire::test(ArusKasBank::class)
        ->set('tableFilters', [
            'tanggal' => [
                'tanggal_mulai' => '2026-02-01',
                'tanggal_akhir' => '2026-02-28',
            ],
        ]);

    $records = collect($component->instance()->getTableRecords());

    expect($records->first()['aktivitas'])->toBe('Operasi');
});

it('cetakPdf tersedia dan callable di ArusKasBank', function () {
    akunArusKas();
    aktorDenganAkses(ArusKasBank::class);

    $component = Livewire::test(ArusKasBank::class)
        ->set('tableFilters', [
            'tanggal' => [
                'tanggal_mulai' => '2026-02-01',
                'tanggal_akhir' => '2026-02-28',
            ],
        ]);

    expect(method_exists($component->instance(), 'getHeaderActions'))->toBeTrue();

    $component->callAction('cetakPdf')->assertHasNoActionErrors();
});

/**
 * Bangun satu tagihan + opsional pembayaran untuk uji laporan pembayaran.
 *
 * @return array{semester: Semester, tagihan: TagihanSiswa}
 */
function tagihanUntukSemesterAktif(int $totalTagihan, ?int $jumlahBayar = null, ?string $tanggalBayar = null): array
{
    $tahunAjaran = TahunAjaran::factory()->create();
    $semester = Semester::factory()->create(['is_active' => true, 'tahun_ajaran_id' => $tahunAjaran->id]);
    $kelas = Kelas::factory()->create(['tahun_ajaran_id' => $tahunAjaran->id]);
    $jenis = JenisPembayaran::factory()->create(['is_active' => true, 'tahun_ajaran_id' => $tahunAjaran->id]);
    $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

    $tagihan = TagihanSiswa::factory()->create([
        'semester_id' => $semester->id,
        'jenis_pembayaran_id' => $jenis->id,
        'siswa_id' => $siswa->id,
        'total_tagihan' => $totalTagihan,
        'sisa_tagihan' => $totalTagihan,
        'total_terbayar' => 0,
        'status' => 'sebagian',
    ]);

    if ($jumlahBayar !== null) {
        Pembayaran::factory()->create([
            'tagihan_siswa_id' => $tagihan->id,
            'jumlah_bayar' => $jumlahBayar,
            'status' => 'berhasil',
            'tanggal_bayar' => $tanggalBayar ?? now()->toDateString(),
        ]);

        $tagihan->refresh();
    }

    return ['semester' => $semester, 'tagihan' => $tagihan];
}

it('laporan pembayaran memakai sisa_tagihan riil untuk Sisa Tagihan, bukan total semester dikurangi pembayaran periode', function () {
    aktorDenganAkses(LaporanPembayaran::class);

    // Bayar 400.000 di luar window (2020) atas tagihan 1.000.000.
    $data = tagihanUntukSemesterAktif(1000000, 400000, '2020-01-15');

    $component = Livewire::test(LaporanPembayaran::class)
        ->set('tableFilters', [
            'semester_id' => ['value' => $data['semester']->id],
            'tanggal' => [
                'tanggal_mulai' => '2026-01-01',
                'tanggal_selesai' => '2026-01-31',
            ],
        ]);

    $component->instance()->getTableRecords();
    $summary = $component->get('summary');

    // Pembayaran riil mengurangi sisa menjadi 600.000 walau di luar window;
    // "terbayar (periode ini)" = 0 karena pembayaran di luar rentang tanggal.
    expect((float) $summary['terbayar_periode'])->toBe(0.0)
        ->and((float) $summary['total_sisa'])->toBe(600000.0)
        ->and((float) $summary['total_tagihan'])->toBe(1000000.0);
});

it('cetakPdf tersedia dan callable di LaporanPembayaran', function () {
    aktorDenganAkses(LaporanPembayaran::class);
    $data = tagihanUntukSemesterAktif(500000, 200000);

    $component = Livewire::test(LaporanPembayaran::class)
        ->set('tableFilters', [
            'semester_id' => ['value' => $data['semester']->id],
            'tanggal' => [
                'tanggal_mulai' => now()->startOfMonth()->toDateString(),
                'tanggal_selesai' => now()->endOfMonth()->toDateString(),
            ],
        ]);

    $component->callAction('cetakPdf')->assertHasNoActionErrors();
});

it('laporan tagihan siswa mengecualikan tagihan batal dari agregat nominal', function () {
    aktorDenganAkses(LaporanTagihanSiswa::class);

    $tahunAjaran = TahunAjaran::factory()->create();
    $semester = Semester::factory()->create(['is_active' => true, 'tahun_ajaran_id' => $tahunAjaran->id]);
    $kelas = Kelas::factory()->create(['tahun_ajaran_id' => $tahunAjaran->id]);
    $jenis = JenisPembayaran::factory()->create(['is_active' => true, 'tahun_ajaran_id' => $tahunAjaran->id]);
    $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

    // Tagihan aktif 1.000.000 (belum dibayar).
    TagihanSiswa::factory()->create([
        'semester_id' => $semester->id,
        'jenis_pembayaran_id' => $jenis->id,
        'siswa_id' => $siswa->id,
        'total_tagihan' => 1000000,
        'sisa_tagihan' => 1000000,
        'total_terbayar' => 0,
        'status' => 'belum_bayar',
    ]);

    // Tagihan batal 750.000 -> tidak boleh masuk agregat nominal.
    TagihanSiswa::factory()->create([
        'semester_id' => $semester->id,
        'jenis_pembayaran_id' => $jenis->id,
        'siswa_id' => $siswa->id,
        'total_tagihan' => 750000,
        'sisa_tagihan' => 750000,
        'total_terbayar' => 0,
        'status' => 'batal',
    ]);

    $widget = Livewire::test(LaporanTagihanSiswaStats::class)->instance();

    $getStats = (new ReflectionMethod($widget, 'getStats'));
    $getStats->setAccessible(true);
    $stats = $getStats->invoke($widget);

    $nilai = collect($stats)->mapWithKeys(fn ($stat) => [$stat->getLabel() => $stat->getValue()]);

    // Total tagihan & sisa hanya menghitung tagihan aktif (1.000.000), bukan
    // 1.750.000 (#79).
    expect($nilai->get('Total Tagihan'))->toBe('Rp 1.000.000')
        ->and($nilai->get('Sisa Tagihan'))->toBe('Rp 1.000.000');
});

it('cetakPdf tersedia dan callable di LaporanTagihanSiswa', function () {
    aktorDenganAkses(LaporanTagihanSiswa::class);

    $tahunAjaran = TahunAjaran::factory()->create();
    $semester = Semester::factory()->create(['is_active' => true, 'tahun_ajaran_id' => $tahunAjaran->id]);
    $kelas = Kelas::factory()->create(['tahun_ajaran_id' => $tahunAjaran->id]);
    $jenis = JenisPembayaran::factory()->create(['is_active' => true, 'tahun_ajaran_id' => $tahunAjaran->id]);
    $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

    TagihanSiswa::factory()->create([
        'semester_id' => $semester->id,
        'jenis_pembayaran_id' => $jenis->id,
        'siswa_id' => $siswa->id,
        'status' => 'belum_bayar',
    ]);

    $component = Livewire::test(LaporanTagihanSiswa::class);

    $component->callAction('cetakPdf')->assertHasNoActionErrors();
});

it('pembayaran per kelas memetakan siswa ke kelas historis via kenaikan kelas untuk semester lampau (#97)', function () {
    aktorDenganAkses(LaporanPembayaranPerKelas::class);

    $tahunAjaran = TahunAjaran::factory()->create();
    // Semester historis (TIDAK aktif).
    $semesterLampau = Semester::factory()->create([
        'is_active' => false,
        'tahun_ajaran_id' => $tahunAjaran->id,
        'semester' => 1,
    ]);

    $kelasLama = Kelas::factory()->create(['nama' => 'Kelas 7A', 'tahun_ajaran_id' => $tahunAjaran->id]);
    $kelasSekarang = Kelas::factory()->create(['nama' => 'Kelas 8A', 'tahun_ajaran_id' => $tahunAjaran->id]);

    // Siswa kini berada di Kelas 8A, tapi pada semester lampau berada di 7A.
    $siswa = Siswa::factory()->create(['kelas_id' => $kelasSekarang->id]);

    KenaikanKelas::factory()->create([
        'siswa_id' => $siswa->id,
        'semester_id' => $semesterLampau->id,
        'kelas_asal_id' => $kelasLama->id,
        'kelas_tujuan_id' => $kelasSekarang->id,
        'status' => 'naik',
    ]);

    $jenis = JenisPembayaran::factory()->create(['is_active' => true, 'tahun_ajaran_id' => $tahunAjaran->id]);

    TagihanSiswa::factory()->create([
        'semester_id' => $semesterLampau->id,
        'jenis_pembayaran_id' => $jenis->id,
        'siswa_id' => $siswa->id,
        'total_tagihan' => 500000,
        'sisa_tagihan' => 500000,
        'total_terbayar' => 0,
        'status' => 'belum_bayar',
    ]);

    // Memfilter Kelas 7A (kelas LAMA) untuk semester lampau harus menampilkan
    // siswa, karena pemetaan memakai kelas historis (kelas_asal_id), bukan
    // kelas_id siswa saat ini (8A).
    $component = Livewire::test(LaporanPembayaranPerKelas::class)
        ->set('tableFilters', [
            'semester_id' => ['value' => $semesterLampau->id],
            'kelas_id' => ['value' => $kelasLama->id],
        ]);

    $records = collect($component->instance()->getTableRecords());
    $summary = $component->get('summary');
    $basis = $component->get('basisKelas');

    expect($records)->toHaveCount(1)
        ->and((float) $summary['total_tagihan'])->toBe(500000.0)
        ->and($basis)->toBe('historis');

    // Memfilter Kelas 8A (kelas SEKARANG) untuk semester lampau TIDAK boleh
    // menampilkan siswa tsb, karena pada semester itu ia belum di 8A.
    $componentSekarang = Livewire::test(LaporanPembayaranPerKelas::class)
        ->set('tableFilters', [
            'semester_id' => ['value' => $semesterLampau->id],
            'kelas_id' => ['value' => $kelasSekarang->id],
        ]);

    expect(collect($componentSekarang->instance()->getTableRecords()))->toHaveCount(0);
});

it('cetakPdf tersedia dan callable di LaporanKeuangan', function () {
    aktorDenganAkses(LaporanKeuangan::class);
    tagihanUntukSemesterAktif(500000, 200000);

    Livewire::test(LaporanKeuangan::class)
        ->set('tableFilters', [
            'tanggal' => [
                'tanggal_mulai' => now()->startOfMonth()->toDateString(),
                'tanggal_akhir' => now()->endOfMonth()->toDateString(),
            ],
        ])
        ->callAction('cetakPdf')
        ->assertHasNoActionErrors();
});

it('cetakPdf tersedia dan callable di LaporanPembayaranPerTanggal', function () {
    aktorDenganAkses(LaporanPembayaranPerTanggal::class);
    tagihanUntukSemesterAktif(500000, 200000);

    Livewire::test(LaporanPembayaranPerTanggal::class)
        ->set('tableFilters', [
            'tanggal' => [
                'tanggal_mulai' => now()->startOfMonth()->toDateString(),
                'tanggal_selesai' => now()->endOfMonth()->toDateString(),
            ],
        ])
        ->callAction('cetakPdf')
        ->assertHasNoActionErrors();
});

it('cetakPdf tersedia dan callable di LaporanUnitPos', function () {
    aktorDenganAkses(LaporanUnitPos::class);
    tagihanUntukSemesterAktif(500000, 200000);

    Livewire::test(LaporanUnitPos::class)
        ->set('tableFilters', [
            'tanggal' => [
                'tanggal_mulai' => now()->startOfMonth()->toDateString(),
                'tanggal_selesai' => now()->endOfMonth()->toDateString(),
            ],
        ])
        ->callAction('cetakPdf')
        ->assertHasNoActionErrors();
});
