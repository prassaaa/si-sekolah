<?php

use App\Filament\Resources\JurnalUmums\Pages\CreateJurnalUmum;
use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permissions = [
        'ViewAny:JurnalUmum', 'View:JurnalUmum', 'Create:JurnalUmum',
        'Update:JurnalUmum', 'Delete:JurnalUmum', 'DeleteAny:JurnalUmum',
        'ForceDelete:JurnalUmum', 'ForceDeleteAny:JurnalUmum',
        'Restore:JurnalUmum', 'RestoreAny:JurnalUmum',
        'Replicate:JurnalUmum', 'Reorder:JurnalUmum',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    $this->actingAs($user);
});

it('membuat 2 baris jurnal balanced dan berbagi nomor_bukti yang sama', function () {
    $akun1 = Akun::factory()->aset()->create();
    $akun2 = Akun::factory()->pendapatan()->create();

    Livewire::test(CreateJurnalUmum::class)
        ->fillForm([
            'tanggal' => '2026-06-13',
            'keterangan' => 'Penerimaan uang sekolah',
            'details' => [
                ['akun_id' => $akun1->id, 'debit' => 500000, 'kredit' => 0],
                ['akun_id' => $akun2->id, 'debit' => 0, 'kredit' => 500000],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(JurnalUmum::count())->toBe(2);

    $rows = JurnalUmum::all();
    $nomorBukti = $rows->first()->nomor_bukti;

    expect($rows->every(fn ($r) => $r->nomor_bukti === $nomorBukti))->toBeTrue()
        ->and((float) $rows->sum('debit'))->toBe((float) $rows->sum('kredit'));
});

it('menolak entri tidak balance dan tidak menyimpan baris apapun', function () {
    $akun1 = Akun::factory()->aset()->create();
    $akun2 = Akun::factory()->pendapatan()->create();

    $jumlahSebelum = JurnalUmum::count();

    Livewire::test(CreateJurnalUmum::class)
        ->fillForm([
            'tanggal' => '2026-06-13',
            'keterangan' => 'Entri salah',
            'details' => [
                ['akun_id' => $akun1->id, 'debit' => 300000, 'kredit' => 0],
                ['akun_id' => $akun2->id, 'debit' => 0, 'kredit' => 200000],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['details']);

    expect(JurnalUmum::count())->toBe($jumlahSebelum);
});

it('membuat 3 baris balanced (1 debit, 2 kredit) dengan nomor_bukti sama', function () {
    $kasAkun = Akun::factory()->aset()->create();
    $pendapatan1 = Akun::factory()->pendapatan()->create();
    $pendapatan2 = Akun::factory()->pendapatan()->create();

    Livewire::test(CreateJurnalUmum::class)
        ->fillForm([
            'tanggal' => '2026-06-13',
            'keterangan' => 'Penerimaan campuran',
            'details' => [
                ['akun_id' => $kasAkun->id, 'debit' => 1500000, 'kredit' => 0],
                ['akun_id' => $pendapatan1->id, 'debit' => 0, 'kredit' => 1000000],
                ['akun_id' => $pendapatan2->id, 'debit' => 0, 'kredit' => 500000],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(JurnalUmum::count())->toBe(3);

    $rows = JurnalUmum::all();
    $nomors = $rows->pluck('nomor_bukti')->unique();

    expect($nomors)->toHaveCount(1)
        ->and((float) $rows->sum('debit'))->toBe((float) $rows->sum('kredit'));
});

it('dua baris balanced tidak melanggar constraint database', function () {
    $akun1 = Akun::factory()->aset()->create();
    $akun2 = Akun::factory()->beban()->create();

    Livewire::test(CreateJurnalUmum::class)
        ->fillForm([
            'tanggal' => '2026-06-13',
            'keterangan' => 'Transaksi pertama',
            'details' => [
                ['akun_id' => $akun1->id, 'debit' => 750000, 'kredit' => 0],
                ['akun_id' => $akun2->id, 'debit' => 0, 'kredit' => 750000],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $nomorPertama = JurnalUmum::first()->nomor_bukti;

    expect(JurnalUmum::where('nomor_bukti', $nomorPertama)->count())->toBe(2);

    Livewire::test(CreateJurnalUmum::class)
        ->fillForm([
            'tanggal' => '2026-06-13',
            'keterangan' => 'Transaksi kedua',
            'details' => [
                ['akun_id' => $akun1->id, 'debit' => 200000, 'kredit' => 0],
                ['akun_id' => $akun2->id, 'debit' => 0, 'kredit' => 200000],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(JurnalUmum::count())->toBe(4)
        ->and(JurnalUmum::select('nomor_bukti')->distinct()->count('nomor_bukti'))->toBe(2);
});

it('menolak entri dengan total debit dan kredit keduanya nol', function () {
    $akun1 = Akun::factory()->aset()->create();
    $akun2 = Akun::factory()->pendapatan()->create();

    $jumlahSebelum = JurnalUmum::count();

    Livewire::test(CreateJurnalUmum::class)
        ->fillForm([
            'tanggal' => '2026-06-13',
            'keterangan' => 'Entri nol',
            'details' => [
                ['akun_id' => $akun1->id, 'debit' => 0, 'kredit' => 0],
                ['akun_id' => $akun2->id, 'debit' => 0, 'kredit' => 0],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['details']);

    expect(JurnalUmum::count())->toBe($jumlahSebelum);
});
