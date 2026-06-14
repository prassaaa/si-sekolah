<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Menjaga agar pencarian global tidak pernah menabrak kolom yang sebenarnya
 * accessor (bukan kolom DB). Bug seperti recordTitleAttribute = 'jadwal_lengkap'
 * (accessor) membuat WHERE SQL ke kolom tak ada → 500. Test ini menjalankan
 * pencarian global untuk SETIAP resource yang searchable dan memastikan tak ada
 * yang melempar exception.
 */
it('pencarian global berjalan tanpa error untuk semua resource', function (): void {
    $this->seed(RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $this->actingAs($user);

    $panel = Filament::getPanel('auth');
    Filament::setCurrentPanel($panel);

    $gagal = [];

    foreach ($panel->getResources() as $resource) {
        if (! $resource::canGloballySearch()) {
            continue;
        }

        try {
            $resource::getGlobalSearchResults('jadwal');
        } catch (Throwable $e) {
            $gagal[] = class_basename($resource).': '.$e->getMessage();
        }
    }

    expect($gagal)->toBe([]);
});
