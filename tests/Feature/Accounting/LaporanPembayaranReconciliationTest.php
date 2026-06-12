<?php

use App\Filament\Pages\LaporanPembayaran;
use App\Models\JenisPembayaran;
use App\Models\Kelas;
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

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'View:LaporanPembayaran']);

    $user = User::factory()->create();
    $user->givePermissionTo('View:LaporanPembayaran');
    $this->actingAs($user);
});

it('reconciles tagihan, terbayar and sisa in the summary', function () {
    $tahunAjaran = TahunAjaran::factory()->create();
    $semester = Semester::factory()->create(['is_active' => true, 'tahun_ajaran_id' => $tahunAjaran->id]);
    $kelas = Kelas::factory()->create(['tahun_ajaran_id' => $tahunAjaran->id]);
    $jenis = JenisPembayaran::factory()->create(['is_active' => true, 'tahun_ajaran_id' => $tahunAjaran->id]);
    $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

    $tagihan = TagihanSiswa::factory()->create([
        'semester_id' => $semester->id,
        'jenis_pembayaran_id' => $jenis->id,
        'siswa_id' => $siswa->id,
        'total_tagihan' => 1000000,
        'sisa_tagihan' => 1000000,
        'total_terbayar' => 0,
        'status' => 'sebagian',
    ]);

    Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'jumlah_bayar' => 400000,
        'status' => 'berhasil',
        'tanggal_bayar' => now(),
    ]);

    $component = Livewire::test(LaporanPembayaran::class)
        ->set('tableFilters', [
            'semester_id' => ['value' => $semester->id],
            'tanggal' => [
                'tanggal_mulai' => now()->startOfMonth()->toDateString(),
                'tanggal_selesai' => now()->endOfMonth()->toDateString(),
            ],
        ]);

    $component->instance()->getTableRecords();

    $summary = $component->get('summary');

    expect((float) $summary['total_tagihan'])->toBe(1000000.0)
        ->and((float) $summary['total_terbayar'])->toBe(400000.0)
        ->and((float) $summary['total_tagihan'])->toBe((float) $summary['total_terbayar'] + (float) $summary['total_sisa']);
});

it('excludes payments outside the date window from terbayar', function () {
    $tahunAjaran = TahunAjaran::factory()->create();
    $semester = Semester::factory()->create(['is_active' => true, 'tahun_ajaran_id' => $tahunAjaran->id]);
    $kelas = Kelas::factory()->create(['tahun_ajaran_id' => $tahunAjaran->id]);
    $jenis = JenisPembayaran::factory()->create(['is_active' => true, 'tahun_ajaran_id' => $tahunAjaran->id]);
    $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

    $tagihan = TagihanSiswa::factory()->create([
        'semester_id' => $semester->id,
        'jenis_pembayaran_id' => $jenis->id,
        'siswa_id' => $siswa->id,
        'total_tagihan' => 1000000,
        'sisa_tagihan' => 1000000,
        'total_terbayar' => 0,
        'status' => 'sebagian',
    ]);

    Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'jumlah_bayar' => 400000,
        'status' => 'berhasil',
        'tanggal_bayar' => '2020-01-15',
    ]);

    $component = Livewire::test(LaporanPembayaran::class)
        ->set('tableFilters', [
            'semester_id' => ['value' => $semester->id],
            'tanggal' => [
                'tanggal_mulai' => '2026-01-01',
                'tanggal_selesai' => '2026-01-31',
            ],
        ]);

    $component->instance()->getTableRecords();

    $summary = $component->get('summary');

    expect((float) $summary['total_terbayar'])->toBe(0.0)
        ->and((float) $summary['total_sisa'])->toBe(1000000.0);
});
