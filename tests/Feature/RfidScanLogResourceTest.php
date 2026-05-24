<?php

use App\Filament\Resources\RfidScanLogs\Pages\ListRfidScanLogs;
use App\Filament\Resources\RfidScanLogs\Pages\ViewRfidScanLog;
use App\Filament\Resources\RfidScanLogs\RfidScanLogResource;
use App\Models\RfidScanLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permissions = ['ViewAny:RfidScanLog', 'View:RfidScanLog'];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    $this->actingAs($user);
});

it('renders the list page', function () {
    Livewire::test(ListRfidScanLogs::class)->assertOk();
});

it('lists scan log records', function () {
    $records = RfidScanLog::factory()->count(3)->create();

    Livewire::test(ListRfidScanLogs::class)
        ->assertCanSeeTableRecords($records);
});

it('renders the view page', function () {
    $log = RfidScanLog::factory()->masuk()->create();

    Livewire::test(ViewRfidScanLog::class, ['record' => $log->id])->assertOk();
});

it('cannot create scan logs from UI', function () {
    expect(RfidScanLogResource::canCreate())->toBeFalse();
});

it('cannot edit scan logs from UI', function () {
    $log = RfidScanLog::factory()->create();
    expect(RfidScanLogResource::canEdit($log))->toBeFalse();
});

it('cannot delete scan logs from UI', function () {
    $log = RfidScanLog::factory()->create();
    expect(RfidScanLogResource::canDelete($log))->toBeFalse();
});

it('filters by jenis', function () {
    $masuk = RfidScanLog::factory()->masuk()->create();
    $ditolak = RfidScanLog::factory()->ditolak()->create();

    Livewire::test(ListRfidScanLogs::class)
        ->filterTable('jenis', ['ditolak'])
        ->assertCanSeeTableRecords([$ditolak])
        ->assertCanNotSeeTableRecords([$masuk]);
});

it('filters hanya gagal shows only ditolak and tidak_dikenal', function () {
    $masuk = RfidScanLog::factory()->masuk()->create();
    $ditolak = RfidScanLog::factory()->ditolak()->create();
    $unknown = RfidScanLog::factory()->tidakDikenal()->create();

    Livewire::test(ListRfidScanLogs::class)
        ->filterTable('hanya_gagal')
        ->assertCanSeeTableRecords([$ditolak, $unknown])
        ->assertCanNotSeeTableRecords([$masuk]);
});
