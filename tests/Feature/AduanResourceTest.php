<?php

use App\Filament\Resources\Aduans\AduanResource;
use App\Filament\Resources\Aduans\Pages\CreateAduan;
use App\Filament\Resources\Aduans\Pages\EditAduan;
use App\Filament\Resources\Aduans\Pages\ListAduans;
use App\Filament\Resources\Aduans\Pages\ViewAduan;
use App\Models\Aduan;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permissions = [
        'ViewAny:Aduan', 'View:Aduan', 'Create:Aduan',
        'Update:Aduan', 'Delete:Aduan', 'DeleteAny:Aduan',
        'ForceDelete:Aduan', 'ForceDeleteAny:Aduan',
        'Restore:Aduan', 'RestoreAny:Aduan',
        'Replicate:Aduan', 'Reorder:Aduan',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    $this->actingAs($user);
});

it('renders the list page', function () {
    Livewire::test(ListAduans::class)->assertOk();
});

it('lists aduan records', function () {
    $records = Aduan::factory()->count(3)->create();

    Livewire::test(ListAduans::class)
        ->assertCanSeeTableRecords($records);
});

it('renders the create page', function () {
    Livewire::test(CreateAduan::class)->assertOk();
});

it('creates an aduan', function () {
    $siswa = Siswa::factory()->create();

    Livewire::test(CreateAduan::class)
        ->fillForm([
            'pelapor' => 'Ahmad Budiman',
            'hubungan_pelapor' => 'ayah',
            'kontak_pelapor' => '081234567890',
            'siswa_id' => $siswa->id,
            'tanggal_aduan' => now()->toDateString(),
            'kategori' => 'akademik',
            'judul' => 'Nilai raport tidak sesuai',
            'isi' => 'Nilai raport anak saya tidak sesuai dengan hasil ujian yang sebenarnya.',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('aduans', [
        'pelapor' => 'Ahmad Budiman',
        'judul' => 'Nilai raport tidak sesuai',
        'kategori' => 'akademik',
        'status' => 'baru',
    ]);
});

it('validates required fields on create', function () {
    Livewire::test(CreateAduan::class)
        ->fillForm([
            'pelapor' => '',
            'judul' => '',
            'isi' => '',
        ])
        ->call('create')
        ->assertHasFormErrors(['pelapor', 'judul', 'isi']);
});

it('validates required kategori on create', function () {
    Livewire::test(CreateAduan::class)
        ->fillForm([
            'pelapor' => 'Ahmad Budiman',
            'judul' => 'Judul aduan',
            'isi' => 'Isi aduan yang cukup panjang.',
            'kategori' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['kategori']);
});

it('renders the view page', function () {
    $aduan = Aduan::factory()->create();

    Livewire::test(ViewAduan::class, ['record' => $aduan->id])
        ->assertOk()
        ->assertSee($aduan->judul);
});

it('renders the edit page', function () {
    $aduan = Aduan::factory()->create();

    Livewire::test(EditAduan::class, ['record' => $aduan->id])
        ->assertOk();
});

it('updates an aduan', function () {
    $aduan = Aduan::factory()->create(['judul' => 'Judul Lama', 'status' => 'baru']);

    Livewire::test(EditAduan::class, ['record' => $aduan->id])
        ->fillForm(['judul' => 'Judul Baru'])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('aduans', [
        'id' => $aduan->id,
        'judul' => 'Judul Baru',
    ]);
});

it('tanggapi action sets status tanggapan and tanggal_tanggapan', function () {
    $aduan = Aduan::factory()->create(['status' => 'baru']);

    Livewire::test(ListAduans::class)
        ->callTableAction('tanggapi', $aduan, data: [
            'status' => 'selesai',
            'tanggapan' => 'Aduan sudah ditindaklanjuti.',
        ])
        ->assertHasNoTableActionErrors();

    $aduan->refresh();

    expect($aduan->status)->toBe('selesai');
    expect($aduan->tanggapan)->toBe('Aduan sudah ditindaklanjuti.');
    expect($aduan->tanggal_tanggapan)->not->toBeNull();
});

it('tanggapi action is hidden for selesai status', function () {
    $aduan = Aduan::factory()->create(['status' => 'selesai']);

    Livewire::test(ListAduans::class)
        ->assertTableActionHidden('tanggapi', $aduan);
});

it('user without ViewAny permission cannot be given access to aduan', function () {
    $userWithoutPermission = User::factory()->create();

    expect($userWithoutPermission->hasPermissionTo('ViewAny:Aduan'))->toBeFalse();
});

it('soft deletes an aduan', function () {
    $aduan = Aduan::factory()->create();

    Livewire::test(EditAduan::class, ['record' => $aduan->id])
        ->callAction('delete');

    $this->assertSoftDeleted('aduans', ['id' => $aduan->id]);
});

it('restores a soft-deleted aduan', function () {
    $aduan = Aduan::factory()->create();
    $aduan->delete();

    expect(Aduan::withTrashed()->find($aduan->id)->deleted_at)->not->toBeNull();

    $aduan->restore();

    expect(Aduan::find($aduan->id)->deleted_at)->toBeNull();
});

it('navigation badge counts status baru', function () {
    Aduan::factory()->count(3)->create(['status' => 'baru']);
    Aduan::factory()->count(2)->create(['status' => 'selesai']);

    $badge = AduanResource::getNavigationBadge();

    expect($badge)->toBe('3');
});

it('navigation badge is null when no baru aduan', function () {
    Aduan::factory()->count(2)->create(['status' => 'selesai']);

    $badge = AduanResource::getNavigationBadge();

    expect($badge)->toBeNull();
});
