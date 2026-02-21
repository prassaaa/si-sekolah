<?php

namespace App\Policies;

use App\Models\Absensi;
use App\Models\User;

class AbsensiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Absensi');
    }

    public function view(User $user, Absensi $absensi): bool
    {
        return $user->can('View:Absensi');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Absensi');
    }

    public function update(User $user, Absensi $absensi): bool
    {
        return $user->can('Update:Absensi');
    }

    public function delete(User $user, Absensi $absensi): bool
    {
        return $user->can('Delete:Absensi');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Absensi');
    }

    public function restore(User $user, Absensi $absensi): bool
    {
        return $user->can('Restore:Absensi');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Absensi');
    }

    public function forceDelete(User $user, Absensi $absensi): bool
    {
        return $user->can('ForceDelete:Absensi');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Absensi');
    }

    public function replicate(User $user, Absensi $absensi): bool
    {
        return $user->can('Replicate:Absensi');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:Absensi');
    }
}
