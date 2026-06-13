<?php

use App\Filament\Resources\KasMasuks\Pages\CreateKasMasuk;
use App\Models\Akun;
use App\Models\JenisPembayaran;
use App\Models\JurnalUmum;
use App\Models\KasMasuk;
use App\Models\Pembayaran;
use App\Models\TagihanSiswa;
use App\Models\UnitPos;
use App\Models\User;
use App\Services\Accounting\PembayaranJournalPoster;
use Database\Seeders\AkunSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed(AkunSeeder::class);

    $this->kasDefault = Akun::query()->where('kode', '1-1001')->firstOrFail();
    $this->pendapatanDefault = Akun::query()->where('kode', '4-1001')->firstOrFail();
});

/**
 * Membuat tagihan dengan ruang cukup agar pembayaran berhasil lolos
 * validasi overpayment (Wave 1).
 */
function tagihanWithRoom(int $total = 1000000): TagihanSiswa
{
    return TagihanSiswa::factory()->belumBayar()->create([
        'nominal' => $total,
        'total_tagihan' => $total,
        'total_terbayar' => 0,
        'sisa_tagihan' => $total,
    ]);
}

function activeJurnal(Pembayaran $pembayaran)
{
    return JurnalUmum::query()
        ->where('jenis_referensi', PembayaranJournalPoster::JENIS)
        ->where('referensi_id', $pembayaran->getKey())
        ->get();
}

it('posts a balanced pair debiting unit pos akun and crediting jenis pembayaran akun', function () {
    $kasUnit = Akun::query()->where('kode', '1-1002')->firstOrFail(); // Bank BCA
    $pendapatanGedung = Akun::query()->where('kode', '4-1002')->firstOrFail();

    $unitPos = UnitPos::factory()->create([
        'kode' => 'UP-01',
        'nama' => 'Unit Pos Utama',
        'akun_id' => $kasUnit->id,
    ]);
    $jenis = JenisPembayaran::factory()->create(['akun_pendapatan_id' => $pendapatanGedung->id]);
    $tagihan = tagihanWithRoom();
    $tagihan->update(['jenis_pembayaran_id' => $jenis->id]);

    $pembayaran = Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'unit_pos_id' => $unitPos->id,
        'tanggal_bayar' => '2026-07-15',
        'jumlah_bayar' => 750000,
        'status' => 'berhasil',
    ]);

    $entries = activeJurnal($pembayaran);

    expect($entries)->toHaveCount(2)
        ->and((string) $entries->sum('debit'))->toBe((string) $entries->sum('kredit'));

    $debit = $entries->firstWhere('akun_id', $kasUnit->id);
    $kredit = $entries->firstWhere('akun_id', $pendapatanGedung->id);

    expect((float) $debit->debit)->toBe(750000.0)
        ->and((float) $kredit->kredit)->toBe(750000.0)
        ->and($debit->jenis_referensi)->toBe('pembayaran');
});

it('falls back to default cash akun when unit_pos_id is null', function () {
    $tagihan = tagihanWithRoom();

    $pembayaran = Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'unit_pos_id' => null,
        'tanggal_bayar' => '2026-07-15',
        'jumlah_bayar' => 500000,
        'status' => 'berhasil',
    ]);

    $entries = activeJurnal($pembayaran);
    $debit = $entries->firstWhere('debit', '>', 0);

    expect($entries)->toHaveCount(2)
        ->and((int) $debit->akun_id)->toBe($this->kasDefault->id);
});

it('falls back to default pendapatan akun when jenis pembayaran has no akun', function () {
    $jenis = JenisPembayaran::factory()->create(['akun_pendapatan_id' => null]);
    $tagihan = tagihanWithRoom();
    $tagihan->update(['jenis_pembayaran_id' => $jenis->id]);

    $pembayaran = Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'unit_pos_id' => null,
        'tanggal_bayar' => '2026-07-15',
        'jumlah_bayar' => 500000,
        'status' => 'berhasil',
    ]);

    $entries = activeJurnal($pembayaran);
    $kredit = $entries->firstWhere('kredit', '>', 0);

    expect($entries)->toHaveCount(2)
        ->and((int) $kredit->akun_id)->toBe($this->pendapatanDefault->id);
});

it('does not post when payment status is pending', function () {
    $tagihan = tagihanWithRoom();

    $pembayaran = Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'tanggal_bayar' => '2026-07-15',
        'jumlah_bayar' => 500000,
        'status' => 'pending',
    ]);

    expect(activeJurnal($pembayaran))->toHaveCount(0);
});

it('does not post when tanggal_bayar is before the posting cutoff', function () {
    $tagihan = tagihanWithRoom();

    $pembayaran = Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'tanggal_bayar' => '2026-06-01',
        'jumlah_bayar' => 500000,
        'status' => 'berhasil',
    ]);

    expect(activeJurnal($pembayaran))->toHaveCount(0);
});

it('reverses and reposts with the new nominal when jumlah_bayar is updated', function () {
    $tagihan = tagihanWithRoom();

    $pembayaran = Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'unit_pos_id' => null,
        'tanggal_bayar' => '2026-07-15',
        'jumlah_bayar' => 500000,
        'status' => 'berhasil',
    ]);

    $pembayaran->update(['jumlah_bayar' => 800000]);

    $entries = activeJurnal($pembayaran);

    expect($entries)->toHaveCount(2)
        ->and((float) $entries->firstWhere('debit', '>', 0)->debit)->toBe(800000.0)
        ->and((float) $entries->firstWhere('kredit', '>', 0)->kredit)->toBe(800000.0);
});

it('reverses the journal when status changes from berhasil to batal', function () {
    $tagihan = tagihanWithRoom();

    $pembayaran = Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'tanggal_bayar' => '2026-07-15',
        'jumlah_bayar' => 500000,
        'status' => 'berhasil',
    ]);

    expect(activeJurnal($pembayaran))->toHaveCount(2);

    $pembayaran->update(['status' => 'batal']);

    expect(activeJurnal($pembayaran))->toHaveCount(0)
        ->and(JurnalUmum::withTrashed()
            ->where('jenis_referensi', PembayaranJournalPoster::JENIS)
            ->where('referensi_id', $pembayaran->id)
            ->count())->toBe(2);
});

it('reverses the journal when payment is deleted', function () {
    $tagihan = tagihanWithRoom();

    $pembayaran = Pembayaran::factory()->create([
        'tagihan_siswa_id' => $tagihan->id,
        'tanggal_bayar' => '2026-07-15',
        'jumlah_bayar' => 500000,
        'status' => 'berhasil',
    ]);

    expect(activeJurnal($pembayaran))->toHaveCount(2);

    $pembayaran->delete();

    expect(activeJurnal($pembayaran))->toHaveCount(0);
});

it('rejects a KasMasuk whose lawan akun is the SPP pendapatan account but allows other accounts', function () {
    $permissions = [
        'ViewAny:KasMasuk', 'View:KasMasuk', 'Create:KasMasuk',
        'Update:KasMasuk', 'Delete:KasMasuk', 'DeleteAny:KasMasuk',
        'ForceDelete:KasMasuk', 'ForceDeleteAny:KasMasuk',
        'Restore:KasMasuk', 'RestoreAny:KasMasuk',
        'Replicate:KasMasuk', 'Reorder:KasMasuk',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);
    $this->actingAs($user);

    $bank = Akun::query()->where('kode', '1-1002')->firstOrFail();
    $pendapatanLain = Akun::query()->where('kode', '4-1004')->firstOrFail();

    // Akun lawan = Pendapatan SPP → ditolak
    Livewire::test(CreateKasMasuk::class)
        ->fillForm([
            'kas_akun_id' => $bank->id,
            'akun_id' => $this->pendapatanDefault->id,
            'tanggal' => '2026-07-15',
            'nominal' => 250000,
        ])
        ->call('create')
        ->assertHasFormErrors(['akun_id']);

    expect(KasMasuk::query()->count())->toBe(0);

    // Akun lawan lain (Pendapatan Kegiatan) → lolos
    Livewire::test(CreateKasMasuk::class)
        ->fillForm([
            'kas_akun_id' => $bank->id,
            'akun_id' => $pendapatanLain->id,
            'tanggal' => '2026-07-15',
            'nominal' => 250000,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(KasMasuk::query()->count())->toBe(1);
});
