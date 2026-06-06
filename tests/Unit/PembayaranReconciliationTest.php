<?php

use App\Models\Pembayaran;
use App\Models\TagihanSiswa;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeTagihan(int $total = 500000): TagihanSiswa
{
    return TagihanSiswa::factory()->create([
        'nominal' => $total,
        'diskon' => 0,
        'total_tagihan' => $total,
        'total_terbayar' => 0,
        'sisa_tagihan' => $total,
        'status' => 'belum_bayar',
    ]);
}

it('applies a berhasil payment to its tagihan on create', function () {
    $tagihan = makeTagihan(500000);

    $pembayaran = Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'jumlah_bayar' => 200000,
        'status' => 'berhasil',
    ]);

    $tagihan->refresh();
    $pembayaran->refresh();

    expect((float) $tagihan->total_terbayar)->toBe(200000.0)
        ->and((float) $tagihan->sisa_tagihan)->toBe(300000.0)
        ->and($tagihan->status)->toBe('sebagian')
        ->and((float) $pembayaran->applied_amount)->toBe(200000.0)
        ->and($pembayaran->applied_at)->not->toBeNull();
});

it('does not apply a pending payment on create', function () {
    $tagihan = makeTagihan(500000);

    $pembayaran = Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'jumlah_bayar' => 200000,
        'status' => 'pending',
    ]);

    $tagihan->refresh();
    $pembayaran->refresh();

    expect((float) $tagihan->total_terbayar)->toBe(0.0)
        ->and((float) $tagihan->sisa_tagihan)->toBe(500000.0)
        ->and((float) $pembayaran->applied_amount)->toBe(0.0)
        ->and($pembayaran->applied_at)->toBeNull();
});

it('marks tagihan lunas when fully paid', function () {
    $tagihan = makeTagihan(500000);

    Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'jumlah_bayar' => 500000,
        'status' => 'berhasil',
    ]);

    $tagihan->refresh();

    expect((float) $tagihan->sisa_tagihan)->toBe(0.0)
        ->and($tagihan->status)->toBe('lunas');
});

it('reverses an applied payment on soft delete', function () {
    $tagihan = makeTagihan(500000);

    $pembayaran = Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'jumlah_bayar' => 200000,
        'status' => 'berhasil',
    ]);

    $pembayaran->delete();

    $tagihan->refresh();
    $pembayaran->refresh();

    expect((float) $tagihan->total_terbayar)->toBe(0.0)
        ->and((float) $tagihan->sisa_tagihan)->toBe(500000.0)
        ->and($tagihan->status)->toBe('belum_bayar')
        ->and((float) $pembayaran->applied_amount)->toBe(0.0)
        ->and($pembayaran->applied_at)->toBeNull();
});

it('does not reverse a pending payment on soft delete', function () {
    $tagihan = makeTagihan(500000);

    $pembayaran = Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'jumlah_bayar' => 200000,
        'status' => 'pending',
    ]);

    $pembayaran->delete();

    $tagihan->refresh();

    expect((float) $tagihan->total_terbayar)->toBe(0.0)
        ->and((float) $tagihan->sisa_tagihan)->toBe(500000.0);
});

it('reverses on force delete of an applied payment', function () {
    $tagihan = makeTagihan(500000);

    $pembayaran = Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'jumlah_bayar' => 200000,
        'status' => 'berhasil',
    ]);

    $pembayaran->forceDelete();

    $tagihan->refresh();

    expect((float) $tagihan->total_terbayar)->toBe(0.0)
        ->and((float) $tagihan->sisa_tagihan)->toBe(500000.0)
        ->and($tagihan->status)->toBe('belum_bayar');
});

it('does not double-reverse on force delete after soft delete', function () {
    $tagihan = makeTagihan(500000);

    $pembayaran = Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'jumlah_bayar' => 200000,
        'status' => 'berhasil',
    ]);

    $pembayaran->delete();
    $tagihan->refresh();
    expect((float) $tagihan->total_terbayar)->toBe(0.0);

    $pembayaran->forceDelete();
    $tagihan->refresh();

    expect((float) $tagihan->total_terbayar)->toBe(0.0)
        ->and((float) $tagihan->sisa_tagihan)->toBe(500000.0);
});

it('reapplies on restore of a soft-deleted berhasil payment', function () {
    $tagihan = makeTagihan(500000);

    $pembayaran = Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'jumlah_bayar' => 200000,
        'status' => 'berhasil',
    ]);

    $pembayaran->delete();
    $pembayaran->restore();

    $tagihan->refresh();
    $pembayaran->refresh();

    expect((float) $tagihan->total_terbayar)->toBe(200000.0)
        ->and((float) $pembayaran->applied_amount)->toBe(200000.0);
});

it('reverses when status leaves berhasil on update', function () {
    $tagihan = makeTagihan(500000);

    $pembayaran = Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'jumlah_bayar' => 200000,
        'status' => 'berhasil',
    ]);

    $pembayaran->update(['status' => 'gagal']);

    $tagihan->refresh();
    $pembayaran->refresh();

    expect((float) $tagihan->total_terbayar)->toBe(0.0)
        ->and((float) $tagihan->sisa_tagihan)->toBe(500000.0)
        ->and((float) $pembayaran->applied_amount)->toBe(0.0)
        ->and($pembayaran->applied_at)->toBeNull();
});

it('applies when status enters berhasil on update', function () {
    $tagihan = makeTagihan(500000);

    $pembayaran = Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'jumlah_bayar' => 200000,
        'status' => 'pending',
    ]);

    $pembayaran->update(['status' => 'berhasil']);

    $tagihan->refresh();
    $pembayaran->refresh();

    expect((float) $tagihan->total_terbayar)->toBe(200000.0)
        ->and((float) $pembayaran->applied_amount)->toBe(200000.0);
});

it('reconciles when both tagihan and amount change', function () {
    $tagihanA = makeTagihan(500000);
    $tagihanB = makeTagihan(500000);

    $pembayaran = Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihanA->id,
        'jumlah_bayar' => 200000,
        'status' => 'berhasil',
    ]);

    $pembayaran->update([
        'tagihan_siswa_id' => $tagihanB->id,
        'jumlah_bayar' => 350000,
    ]);

    $tagihanA->refresh();
    $tagihanB->refresh();
    $pembayaran->refresh();

    expect((float) $tagihanA->total_terbayar)->toBe(0.0)
        ->and((float) $tagihanA->sisa_tagihan)->toBe(500000.0)
        ->and($tagihanA->status)->toBe('belum_bayar')
        ->and((float) $tagihanB->total_terbayar)->toBe(350000.0)
        ->and((float) $tagihanB->sisa_tagihan)->toBe(150000.0)
        ->and((float) $pembayaran->applied_amount)->toBe(350000.0);
});

it('updates the amount on the same tagihan', function () {
    $tagihan = makeTagihan(500000);

    $pembayaran = Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'jumlah_bayar' => 200000,
        'status' => 'berhasil',
    ]);

    $pembayaran->update(['jumlah_bayar' => 450000]);

    $tagihan->refresh();
    $pembayaran->refresh();

    expect((float) $tagihan->total_terbayar)->toBe(450000.0)
        ->and((float) $tagihan->sisa_tagihan)->toBe(50000.0)
        ->and((float) $pembayaran->applied_amount)->toBe(450000.0);
});

it('clamps sisa_tagihan at zero on overpayment and flags overpaid', function () {
    $tagihan = makeTagihan(500000);

    Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'jumlah_bayar' => 700000,
        'status' => 'berhasil',
    ]);

    $tagihan->refresh();

    expect((float) $tagihan->total_terbayar)->toBe(700000.0)
        ->and((float) $tagihan->sisa_tagihan)->toBe(0.0)
        ->and($tagihan->status)->toBe('lunas')
        ->and($tagihan->isOverpaid())->toBeTrue();
});

it('does not flip a batal tagihan when reconciling', function () {
    $tagihan = makeTagihan(500000);
    $tagihan->update(['status' => 'batal']);

    Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'jumlah_bayar' => 200000,
        'status' => 'berhasil',
    ]);

    $tagihan->refresh();

    expect($tagihan->status)->toBe('batal');
});

it('keeps total_terbayar + sisa == total across repeated apply and reverse with decimals', function () {
    $tagihan = makeTagihan(100000);
    $tagihan->update([
        'total_tagihan' => '100000.33',
        'sisa_tagihan' => '100000.33',
        'nominal' => '100000.33',
    ]);

    for ($i = 0; $i < 25; $i++) {
        $pembayaran = Pembayaran::factory()->create([
            'tagihan_siswa_id' => $tagihan->id,
            'jumlah_bayar' => '10.01',
            'status' => 'berhasil',
        ]);

        $pembayaran->delete();
    }

    $tagihan->refresh();

    expect((string) $tagihan->total_terbayar)->toBe('0.00')
        ->and((string) $tagihan->sisa_tagihan)->toBe('100000.33');
});

it('preserves invariant total_terbayar + sisa == total after partial decimal apply', function () {
    $tagihan = makeTagihan(100000);
    $tagihan->update([
        'total_tagihan' => '999.99',
        'sisa_tagihan' => '999.99',
        'nominal' => '999.99',
    ]);

    Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'jumlah_bayar' => '333.33',
        'status' => 'berhasil',
    ]);

    $tagihan->refresh();

    $sum = bcadd(
        (string) $tagihan->total_terbayar,
        (string) $tagihan->sisa_tagihan,
        2,
    );

    expect($sum)->toBe('999.99')
        ->and((string) $tagihan->total_terbayar)->toBe('333.33')
        ->and((string) $tagihan->sisa_tagihan)->toBe('666.66');
});
