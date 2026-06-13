<?php

use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\TabunganSiswa;
use App\Models\TagihanSiswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helper lokal
// ---------------------------------------------------------------------------

/**
 * Buat baris tabungan langsung via model (tanpa Filament page).
 * Untuk pengujian validasi overpayment pada pembayaran, bungkus dalam
 * DB::transaction agar lockForUpdate pada assertWithdrawalIsCovered aktif.
 */
function buatTabungan(Siswa $siswa, string $jenis, float $nominal, string $tanggal): TabunganSiswa
{
    return DB::transaction(fn () => TabunganSiswa::create([
        'siswa_id' => $siswa->id,
        'jenis' => $jenis,
        'nominal' => $nominal,
        'tanggal' => $tanggal,
    ]));
}

// ---------------------------------------------------------------------------
// (a) Penarikan melebihi saldo → ditolak, tidak ada row tersisa, saldo utuh
// ---------------------------------------------------------------------------

it('(a) menolak penarikan yang melebihi saldo dan tidak meninggalkan row', function () {
    $siswa = Siswa::factory()->create();

    buatTabungan($siswa, 'setor', 50000, '2026-01-01');

    try {
        buatTabungan($siswa, 'tarik', 80000, '2026-01-02');
        $this->fail('Seharusnya melempar ValidationException');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('nominal');
    }

    // Tidak boleh ada baris tarik tersimpan
    expect(
        TabunganSiswa::where('siswa_id', $siswa->id)->where('jenis', 'tarik')->count()
    )->toBe(0);

    // Saldo setor tetap utuh
    expect(TabunganSiswa::getSaldoSiswa($siswa->id))->toBe(50000.0);
});

// ---------------------------------------------------------------------------
// (b) Penarikan backdated yang menyebabkan saldo negatif di titik tengah timeline
// ---------------------------------------------------------------------------

it('(b) menolak penarikan backdated yang membuat saldo negatif pada transaksi berikutnya', function () {
    $siswa = Siswa::factory()->create();

    // Timeline: setor 100k → tarik 80k → saldo 20k
    buatTabungan($siswa, 'setor', 100000, '2026-01-01');
    buatTabungan($siswa, 'tarik', 80000, '2026-01-03');

    // Backdated setor 10k (antara setor dan tarik) lalu tarik 50k backdated:
    // Setelah setor awal 100k + setor backdated 10k = 110k,
    // lalu tarik backdated 100k → saldo 10k,
    // lalu tarik 80k → saldo menjadi -70k (tidak valid).
    buatTabungan($siswa, 'setor', 10000, '2026-01-02');

    try {
        // Penarikan 100k di tanggal '2026-01-02' (sebelum tarik 80k di '2026-01-03')
        // Setelah: 100k+10k-100k = 10k, lalu tarik 80k → -70k (invalid)
        buatTabungan($siswa, 'tarik', 100000, '2026-01-02');
        $this->fail('Seharusnya melempar ValidationException karena backdated');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('nominal');
    }

    // Tidak boleh ada baris tarik 100k tersimpan
    expect(
        TabunganSiswa::where('siswa_id', $siswa->id)
            ->where('jenis', 'tarik')
            ->where('nominal', 100000)
            ->count()
    )->toBe(0);
});

// ---------------------------------------------------------------------------
// (c) Penarikan valid → sukses, saldo benar
// ---------------------------------------------------------------------------

it('(c) penarikan valid berhasil dengan saldo yang tepat', function () {
    $siswa = Siswa::factory()->create();

    buatTabungan($siswa, 'setor', 200000, '2026-01-01');
    buatTabungan($siswa, 'tarik', 75000, '2026-01-02');

    expect(TabunganSiswa::getSaldoSiswa($siswa->id))->toBe(125000.0);

    expect(
        TabunganSiswa::where('siswa_id', $siswa->id)->count()
    )->toBe(2);
});

// ---------------------------------------------------------------------------
// (d) Setelah penolakan, setoran berikutnya siswa yang sama tetap bisa
// ---------------------------------------------------------------------------

it('(d) setoran berikutnya tetap berhasil setelah penolakan penarikan — tidak ada korupsi', function () {
    $siswa = Siswa::factory()->create();

    buatTabungan($siswa, 'setor', 50000, '2026-01-01');

    // Penarikan gagal
    try {
        buatTabungan($siswa, 'tarik', 999999, '2026-01-02');
    } catch (ValidationException) {
        // diharapkan
    }

    // Setoran baru harus berhasil
    buatTabungan($siswa, 'setor', 30000, '2026-01-03');

    expect(TabunganSiswa::getSaldoSiswa($siswa->id))->toBe(80000.0);
    expect(
        TabunganSiswa::where('siswa_id', $siswa->id)->count()
    )->toBe(2);
});

// ---------------------------------------------------------------------------
// (e) Pembayaran melebihi sisa tagihan via jalur model → ditolak ValidationException
// ---------------------------------------------------------------------------

it('(e) Pembayaran::create dengan jumlah_bayar melebihi sisa tagihan diblokir oleh reconcilePayment', function () {
    $tagihan = TagihanSiswa::factory()->belumBayar()->create([
        'nominal' => 500000,
        'total_tagihan' => 500000,
        'total_terbayar' => 0,
        'sisa_tagihan' => 500000,
    ]);

    $this->expectException(ValidationException::class);

    Pembayaran::create([
        'tagihan_siswa_id' => $tagihan->id,
        'nomor_transaksi' => 'TEST-001',
        'tanggal_bayar' => now()->toDateString(),
        'jumlah_bayar' => 600000, // melebihi sisa 500000
        'metode_pembayaran' => 'tunai',
        'status' => 'berhasil',
    ]);
});

// ---------------------------------------------------------------------------
// (f) Dua pembayaran berurutan — totalnya melebihi sisa → kedua gagal pada yang melebihi
// ---------------------------------------------------------------------------

it('(f) pembayaran kedua yang melebihi sisa setelah pembayaran pertama ditolak', function () {
    $tagihan = TagihanSiswa::factory()->belumBayar()->create([
        'nominal' => 300000,
        'total_tagihan' => 300000,
        'total_terbayar' => 0,
        'sisa_tagihan' => 300000,
    ]);

    // Pembayaran pertama: 200000 (valid)
    Pembayaran::create([
        'tagihan_siswa_id' => $tagihan->id,
        'nomor_transaksi' => 'TEST-F-001',
        'tanggal_bayar' => now()->toDateString(),
        'jumlah_bayar' => 200000,
        'metode_pembayaran' => 'tunai',
        'status' => 'berhasil',
    ]);

    $tagihan->refresh();
    expect((string) $tagihan->total_terbayar)->toBe('200000.00');

    // Pembayaran kedua: 150000 — sisa hanya 100000, harus ditolak
    try {
        Pembayaran::create([
            'tagihan_siswa_id' => $tagihan->id,
            'nomor_transaksi' => 'TEST-F-002',
            'tanggal_bayar' => now()->toDateString(),
            'jumlah_bayar' => 150000,
            'metode_pembayaran' => 'tunai',
            'status' => 'berhasil',
        ]);
        $this->fail('Seharusnya melempar ValidationException');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('jumlah_bayar');
    }

    // total_terbayar harus tetap 200000, tidak berubah
    $tagihan->refresh();
    expect((string) $tagihan->total_terbayar)->toBe('200000.00');

    // Tidak ada pembayaran kedua tersimpan dengan status berhasil yang melebihi
    expect(
        Pembayaran::where('tagihan_siswa_id', $tagihan->id)
            ->where('status', 'berhasil')
            ->count()
    )->toBe(1);
});
