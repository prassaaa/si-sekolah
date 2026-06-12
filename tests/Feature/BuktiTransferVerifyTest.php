<?php

use App\Filament\Resources\BuktiTransfers\Pages\EditBuktiTransfer;
use App\Models\BuktiTransfer;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\TagihanSiswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permissions = [
        'ViewAny:BuktiTransfer', 'View:BuktiTransfer', 'Create:BuktiTransfer',
        'Update:BuktiTransfer', 'Delete:BuktiTransfer', 'DeleteAny:BuktiTransfer',
        'ForceDelete:BuktiTransfer', 'ForceDeleteAny:BuktiTransfer',
        'Restore:BuktiTransfer', 'RestoreAny:BuktiTransfer',
        'Replicate:BuktiTransfer', 'Reorder:BuktiTransfer',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    $this->actingAs($user);
});

it('creates exactly one berhasil Pembayaran when BuktiTransfer is verified', function () {
    $tagihan = TagihanSiswa::factory()->belumBayar()->create();

    $buktiTransfer = BuktiTransfer::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'siswa_id' => $tagihan->siswa_id,
        'status' => 'pending',
        'nominal' => 250000,
    ]);

    Livewire::test(EditBuktiTransfer::class, ['record' => $buktiTransfer->id])
        ->fillForm([
            'siswa_id' => $buktiTransfer->siswa_id,
            'tagihan_siswa_id' => $tagihan->id,
            'nama_pengirim' => $buktiTransfer->nama_pengirim,
            'bank_pengirim' => $buktiTransfer->bank_pengirim,
            'nominal' => 250000,
            'tanggal_transfer' => $buktiTransfer->tanggal_transfer->toDateString(),
            'status' => 'verified',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Pembayaran::query()
        ->where('tagihan_siswa_id', $tagihan->id)
        ->where('referensi_pembayaran', 'BT-'.$buktiTransfer->id)
        ->where('status', 'berhasil')
        ->where('metode_pembayaran', 'transfer')
        ->count()
    )->toBe(1);

    $buktiTransfer->refresh();
    expect($buktiTransfer->status)->toBe('verified');
    expect($buktiTransfer->verified_by)->not->toBeNull();
    expect($buktiTransfer->verified_at)->not->toBeNull();
});

it('is idempotent when verified BuktiTransfer is re-saved', function () {
    $tagihan = TagihanSiswa::factory()->belumBayar()->create();

    $buktiTransfer = BuktiTransfer::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'siswa_id' => $tagihan->siswa_id,
        'status' => 'pending',
        'nominal' => 250000,
    ]);

    // First save — transitions to verified
    Livewire::test(EditBuktiTransfer::class, ['record' => $buktiTransfer->id])
        ->fillForm([
            'siswa_id' => $buktiTransfer->siswa_id,
            'tagihan_siswa_id' => $tagihan->id,
            'nama_pengirim' => $buktiTransfer->nama_pengirim,
            'bank_pengirim' => $buktiTransfer->bank_pengirim,
            'nominal' => 250000,
            'tanggal_transfer' => $buktiTransfer->tanggal_transfer->toDateString(),
            'status' => 'verified',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    // Second save — same status, must not create a second Pembayaran
    Livewire::test(EditBuktiTransfer::class, ['record' => $buktiTransfer->id])
        ->fillForm([
            'siswa_id' => $buktiTransfer->siswa_id,
            'tagihan_siswa_id' => $tagihan->id,
            'nama_pengirim' => $buktiTransfer->nama_pengirim,
            'bank_pengirim' => $buktiTransfer->bank_pengirim,
            'nominal' => 250000,
            'tanggal_transfer' => $buktiTransfer->tanggal_transfer->toDateString(),
            'status' => 'verified',
            'catatan_admin' => 'Re-save test',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Pembayaran::query()
        ->where('tagihan_siswa_id', $tagihan->id)
        ->where('referensi_pembayaran', 'BT-'.$buktiTransfer->id)
        ->count()
    )->toBe(1);
});

it('does not create a Pembayaran when status is pending', function () {
    $tagihan = TagihanSiswa::factory()->belumBayar()->create();

    $buktiTransfer = BuktiTransfer::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'siswa_id' => $tagihan->siswa_id,
        'status' => 'pending',
        'nominal' => 250000,
    ]);

    Livewire::test(EditBuktiTransfer::class, ['record' => $buktiTransfer->id])
        ->fillForm([
            'siswa_id' => $buktiTransfer->siswa_id,
            'tagihan_siswa_id' => $tagihan->id,
            'nama_pengirim' => $buktiTransfer->nama_pengirim,
            'bank_pengirim' => $buktiTransfer->bank_pengirim,
            'nominal' => 250000,
            'tanggal_transfer' => $buktiTransfer->tanggal_transfer->toDateString(),
            'status' => 'pending',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Pembayaran::query()
        ->where('tagihan_siswa_id', $tagihan->id)
        ->count()
    )->toBe(0);
});

it('memindahkan Pembayaran ke tagihan baru ketika tagihan_siswa_id diubah pasca-verified tanpa membuat Pembayaran ganda', function () {
    $nominal = 250000;

    // Kedua tagihan harus milik siswa yang sama agar opsi Select di form valid
    $siswa = Siswa::factory()->create();

    $tagihanLama = TagihanSiswa::factory()->belumBayar()->create([
        'siswa_id' => $siswa->id,
        'nominal' => $nominal,
        'total_tagihan' => $nominal,
        'total_terbayar' => 0,
        'sisa_tagihan' => $nominal,
    ]);

    $tagihanBaru = TagihanSiswa::factory()->belumBayar()->create([
        'siswa_id' => $siswa->id,
        'nominal' => $nominal,
        'total_tagihan' => $nominal,
        'total_terbayar' => 0,
        'sisa_tagihan' => $nominal,
    ]);

    $buktiTransfer = BuktiTransfer::factory()->create([
        'tagihan_siswa_id' => $tagihanLama->id,
        'siswa_id' => $siswa->id,
        'status' => 'pending',
        'nominal' => $nominal,
    ]);

    // Verifikasi awal — Pembayaran pertama diarahkan ke tagihanLama
    Livewire::test(EditBuktiTransfer::class, ['record' => $buktiTransfer->id])
        ->fillForm([
            'siswa_id' => $buktiTransfer->siswa_id,
            'tagihan_siswa_id' => $tagihanLama->id,
            'nama_pengirim' => $buktiTransfer->nama_pengirim,
            'bank_pengirim' => $buktiTransfer->bank_pengirim,
            'nominal' => $nominal,
            'tanggal_transfer' => $buktiTransfer->tanggal_transfer->toDateString(),
            'status' => 'verified',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    // Koreksi tagihan: ganti ke tagihanBaru dan simpan ulang
    Livewire::test(EditBuktiTransfer::class, ['record' => $buktiTransfer->id])
        ->fillForm([
            'siswa_id' => $buktiTransfer->siswa_id,
            'tagihan_siswa_id' => $tagihanBaru->id,
            'nama_pengirim' => $buktiTransfer->nama_pengirim,
            'bank_pengirim' => $buktiTransfer->bank_pengirim,
            'nominal' => $nominal,
            'tanggal_transfer' => $buktiTransfer->tanggal_transfer->toDateString(),
            'status' => 'verified',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    // Harus tetap hanya satu Pembayaran untuk referensi ini
    expect(
        Pembayaran::withTrashed()
            ->where('referensi_pembayaran', 'BT-'.$buktiTransfer->id)
            ->count()
    )->toBe(1);

    // Pembayaran harus berpindah ke tagihanBaru
    $pembayaran = Pembayaran::withTrashed()
        ->where('referensi_pembayaran', 'BT-'.$buktiTransfer->id)
        ->first();

    expect((int) $pembayaran->tagihan_siswa_id)->toBe($tagihanBaru->id);
    expect($pembayaran->deleted_at)->toBeNull();

    // tagihanLama harus sudah di-reverse (sisa kembali ke penuh)
    $tagihanLama->refresh();
    expect((string) $tagihanLama->total_terbayar)->toBe('0.00');
    expect($tagihanLama->status)->toBe('belum_bayar');

    // tagihanBaru harus sudah terbayar
    $tagihanBaru->refresh();
    expect((string) $tagihanBaru->total_terbayar)->toBe(number_format($nominal, 2, '.', ''));
    expect($tagihanBaru->status)->toBe('lunas');
});

it('tidak membuat Pembayaran baru ketika verified BuktiTransfer disimpan ulang tanpa perubahan tagihan', function () {
    $nominal = 250000;

    $tagihan = TagihanSiswa::factory()->belumBayar()->create([
        'nominal' => $nominal,
        'total_tagihan' => $nominal,
        'total_terbayar' => 0,
        'sisa_tagihan' => $nominal,
    ]);

    $buktiTransfer = BuktiTransfer::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'siswa_id' => $tagihan->siswa_id,
        'status' => 'pending',
        'nominal' => $nominal,
    ]);

    // Verifikasi awal
    Livewire::test(EditBuktiTransfer::class, ['record' => $buktiTransfer->id])
        ->fillForm([
            'siswa_id' => $buktiTransfer->siswa_id,
            'tagihan_siswa_id' => $tagihan->id,
            'nama_pengirim' => $buktiTransfer->nama_pengirim,
            'bank_pengirim' => $buktiTransfer->bank_pengirim,
            'nominal' => $nominal,
            'tanggal_transfer' => $buktiTransfer->tanggal_transfer->toDateString(),
            'status' => 'verified',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    // Simpan ulang tanpa mengubah apapun
    Livewire::test(EditBuktiTransfer::class, ['record' => $buktiTransfer->id])
        ->fillForm([
            'siswa_id' => $buktiTransfer->siswa_id,
            'tagihan_siswa_id' => $tagihan->id,
            'nama_pengirim' => $buktiTransfer->nama_pengirim,
            'bank_pengirim' => $buktiTransfer->bank_pengirim,
            'nominal' => $nominal,
            'tanggal_transfer' => $buktiTransfer->tanggal_transfer->toDateString(),
            'status' => 'verified',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(
        Pembayaran::withTrashed()
            ->where('referensi_pembayaran', 'BT-'.$buktiTransfer->id)
            ->count()
    )->toBe(1);

    // total_terbayar tidak boleh terhitung dua kali
    $tagihan->refresh();
    expect((string) $tagihan->total_terbayar)->toBe(number_format($nominal, 2, '.', ''));
    expect($tagihan->status)->toBe('lunas');
});
