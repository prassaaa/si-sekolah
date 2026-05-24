<?php

namespace App\Policies;

use App\Models\PresensiHarianPegawai;
use App\Models\User;

class PresensiHarianPegawaiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:PresensiHarianPegawai');
    }

    public function view(User $user, PresensiHarianPegawai $presensiHarianPegawai): bool
    {
        return $user->can('View:PresensiHarianPegawai');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:PresensiHarianPegawai');
    }

    public function update(User $user, PresensiHarianPegawai $presensiHarianPegawai): bool
    {
        return $user->can('Update:PresensiHarianPegawai');
    }

    public function delete(User $user, PresensiHarianPegawai $presensiHarianPegawai): bool
    {
        return $user->can('Delete:PresensiHarianPegawai');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:PresensiHarianPegawai');
    }

    public function restore(User $user, PresensiHarianPegawai $presensiHarianPegawai): bool
    {
        return $user->can('Restore:PresensiHarianPegawai');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:PresensiHarianPegawai');
    }

    public function forceDelete(User $user, PresensiHarianPegawai $presensiHarianPegawai): bool
    {
        return $user->can('ForceDelete:PresensiHarianPegawai');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:PresensiHarianPegawai');
    }

    public function replicate(User $user, PresensiHarianPegawai $presensiHarianPegawai): bool
    {
        return $user->can('Replicate:PresensiHarianPegawai');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:PresensiHarianPegawai');
    }
}
