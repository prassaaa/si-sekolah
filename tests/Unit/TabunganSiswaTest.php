<?php

use App\Models\Siswa;
use App\Models\TabunganSiswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function createTabungan(Siswa $siswa, string $jenis, float $nominal, string $tanggal): TabunganSiswa
{
    return TabunganSiswa::create([
        'siswa_id' => $siswa->id,
        'jenis' => $jenis,
        'nominal' => $nominal,
        'tanggal' => $tanggal,
    ]);
}

it('computes saldo for setor then tarik', function () {
    $siswa = Siswa::factory()->create();

    $setor = createTabungan($siswa, 'setor', 100000, '2026-01-01');
    $tarik = createTabungan($siswa, 'tarik', 30000, '2026-01-02');

    expect($setor->fresh()->saldo)->toEqual('100000.00');
    expect($tarik->fresh()->saldo)->toEqual('70000.00');
});

it('rejects a tarik that exceeds the available balance', function () {
    $siswa = Siswa::factory()->create();

    createTabungan($siswa, 'setor', 50000, '2026-01-01');

    createTabungan($siswa, 'tarik', 80000, '2026-01-02');
})->throws(ValidationException::class);

it('does not persist a rejected tarik row', function () {
    $siswa = Siswa::factory()->create();

    createTabungan($siswa, 'setor', 50000, '2026-01-01');

    try {
        createTabungan($siswa, 'tarik', 80000, '2026-01-02');
    } catch (ValidationException) {
        // expected
    }

    expect(TabunganSiswa::where('siswa_id', $siswa->id)->where('jenis', 'tarik')->count())
        ->toBe(0);
});

it('recomputes later rows when a mid-history row is deleted', function () {
    $siswa = Siswa::factory()->create();

    $first = createTabungan($siswa, 'setor', 100000, '2026-01-01');
    $second = createTabungan($siswa, 'setor', 50000, '2026-01-02');
    $third = createTabungan($siswa, 'tarik', 20000, '2026-01-03');

    expect($third->fresh()->saldo)->toEqual('130000.00');

    $second->delete();

    expect($first->fresh()->saldo)->toEqual('100000.00');
    expect($third->fresh()->saldo)->toEqual('80000.00');
});

it('recomputes saldo when a row is edited', function () {
    $siswa = Siswa::factory()->create();

    $first = createTabungan($siswa, 'setor', 100000, '2026-01-01');
    $second = createTabungan($siswa, 'tarik', 40000, '2026-01-02');

    expect($second->fresh()->saldo)->toEqual('60000.00');

    $first->update(['nominal' => 200000]);

    expect($first->fresh()->saldo)->toEqual('200000.00');
    expect($second->fresh()->saldo)->toEqual('160000.00');
});

it('recomputes saldo when a soft-deleted row is restored', function () {
    $siswa = Siswa::factory()->create();

    $first = createTabungan($siswa, 'setor', 100000, '2026-01-01');
    $second = createTabungan($siswa, 'setor', 50000, '2026-01-02');

    $first->delete();
    expect($second->fresh()->saldo)->toEqual('50000.00');

    $first->restore();

    expect($first->fresh()->saldo)->toEqual('100000.00');
    expect($second->fresh()->saldo)->toEqual('150000.00');
});

it('recomputes both students when siswa_id changes', function () {
    $siswaA = Siswa::factory()->create();
    $siswaB = Siswa::factory()->create();

    $rowA1 = createTabungan($siswaA, 'setor', 100000, '2026-01-01');
    $rowA2 = createTabungan($siswaA, 'setor', 50000, '2026-01-02');
    $rowB1 = createTabungan($siswaB, 'setor', 10000, '2026-01-01');

    expect($rowA2->fresh()->saldo)->toEqual('150000.00');

    $rowA2->update(['siswa_id' => $siswaB->id]);

    expect($rowA1->fresh()->saldo)->toEqual('100000.00');
    expect($rowB1->fresh()->saldo)->toEqual('10000.00');
    expect($rowA2->fresh()->saldo)->toEqual('60000.00');
});
