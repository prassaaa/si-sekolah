<?php

use App\Models\Semester;
use App\Models\TahunAjaran;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('activate() deactivates only semesters in the same tahun ajaran', function () {
    // Use explicit kode values to avoid unique-constraint collision from the factory.
    $ta1 = TahunAjaran::factory()->create(['kode' => '2024/2025', 'is_active' => true]);
    $ta2 = TahunAjaran::factory()->create(['kode' => '2025/2026', 'is_active' => false]);

    $s1 = Semester::factory()->create(['tahun_ajaran_id' => $ta1->id, 'semester' => 1, 'is_active' => true]);
    $s2 = Semester::factory()->create(['tahun_ajaran_id' => $ta1->id, 'semester' => 2, 'is_active' => false]);

    // Semester in a different tahun ajaran must NOT be touched
    $s3 = Semester::factory()->create(['tahun_ajaran_id' => $ta2->id, 'is_active' => true]);

    $s2->activate();

    expect($s2->fresh()->is_active)->toBeTrue();
    expect($s1->fresh()->is_active)->toBeFalse(); // same TA → deactivated
    expect($s3->fresh()->is_active)->toBeTrue();  // different TA → untouched
});

it('activate() sets the target semester to active', function () {
    $ta = TahunAjaran::factory()->create(['kode' => '2023/2024']);
    $s = Semester::factory()->create(['tahun_ajaran_id' => $ta->id, 'is_active' => false]);

    $s->activate();

    expect($s->fresh()->is_active)->toBeTrue();
});

it('getSemesterLabelAttribute returns Ganjil for semester 1', function () {
    $s = new Semester(['semester' => 1]);
    expect($s->semester_label)->toBe('Ganjil');
});

it('getSemesterLabelAttribute returns Genap for semester 2', function () {
    $s = new Semester(['semester' => 2]);
    expect($s->semester_label)->toBe('Genap');
});
