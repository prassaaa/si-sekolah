<?php

use App\Models\RfidScanLog;
use App\Models\User;
use App\Policies\ActivityPolicy;
use App\Policies\RfidScanLogPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

// ── RfidScanLogPolicy ────────────────────────────────────────────────────────

it('RfidScanLogPolicy denies create', function () {
    $user = User::factory()->create();
    $policy = new RfidScanLogPolicy;

    expect($policy->create($user))->toBeFalse();
});

it('RfidScanLogPolicy denies update', function () {
    $user = User::factory()->create();
    $log = RfidScanLog::factory()->create();
    $policy = new RfidScanLogPolicy;

    expect($policy->update($user, $log))->toBeFalse();
});

it('RfidScanLogPolicy denies delete', function () {
    $user = User::factory()->create();
    $log = RfidScanLog::factory()->create();
    $policy = new RfidScanLogPolicy;

    expect($policy->delete($user, $log))->toBeFalse();
});

it('RfidScanLogPolicy denies deleteAny', function () {
    $user = User::factory()->create();
    $policy = new RfidScanLogPolicy;

    expect($policy->deleteAny($user))->toBeFalse();
});

it('RfidScanLogPolicy denies replicate', function () {
    $user = User::factory()->create();
    $log = RfidScanLog::factory()->create();
    $policy = new RfidScanLogPolicy;

    expect($policy->replicate($user, $log))->toBeFalse();
});

it('RfidScanLogPolicy denies reorder', function () {
    $user = User::factory()->create();
    $policy = new RfidScanLogPolicy;

    expect($policy->reorder($user))->toBeFalse();
});

it('RfidScanLogPolicy allows viewAny with permission', function () {
    Permission::findOrCreate('ViewAny:RfidScanLog', 'web');

    $user = User::factory()->create();
    $user->givePermissionTo('ViewAny:RfidScanLog');
    $policy = new RfidScanLogPolicy;

    expect($policy->viewAny($user))->toBeTrue();
});

// ── ActivityPolicy ───────────────────────────────────────────────────────────

it('ActivityPolicy denies create', function () {
    $user = User::factory()->create();
    $policy = new ActivityPolicy;

    expect($policy->create($user))->toBeFalse();
});

it('ActivityPolicy denies update', function () {
    $user = User::factory()->create();
    $activity = new Activity;
    $policy = new ActivityPolicy;

    expect($policy->update($user, $activity))->toBeFalse();
});

it('ActivityPolicy denies delete', function () {
    $user = User::factory()->create();
    $activity = new Activity;
    $policy = new ActivityPolicy;

    expect($policy->delete($user, $activity))->toBeFalse();
});

it('ActivityPolicy denies deleteAny', function () {
    $user = User::factory()->create();
    $policy = new ActivityPolicy;

    expect($policy->deleteAny($user))->toBeFalse();
});

it('ActivityPolicy denies forceDelete', function () {
    $user = User::factory()->create();
    $activity = new Activity;
    $policy = new ActivityPolicy;

    expect($policy->forceDelete($user, $activity))->toBeFalse();
});

it('ActivityPolicy denies replicate', function () {
    $user = User::factory()->create();
    $activity = new Activity;
    $policy = new ActivityPolicy;

    expect($policy->replicate($user, $activity))->toBeFalse();
});

it('ActivityPolicy denies reorder', function () {
    $user = User::factory()->create();
    $policy = new ActivityPolicy;

    expect($policy->reorder($user))->toBeFalse();
});

it('ActivityPolicy allows viewAny with permission', function () {
    Permission::findOrCreate('ViewAny:Activity', 'web');

    $user = User::factory()->create();
    $user->givePermissionTo('ViewAny:Activity');
    $policy = new ActivityPolicy;

    expect($policy->viewAny($user))->toBeTrue();
});
