<?php

namespace App\Policies;

use App\Models\Siswa;
use App\Models\User;

class SiswaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Siswa');
    }

    public function view(User $user, Siswa $siswa): bool
    {
        return $user->can('View:Siswa');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Siswa');
    }

    public function update(User $user, Siswa $siswa): bool
    {
        return $user->can('Update:Siswa');
    }

    public function delete(User $user, Siswa $siswa): bool
    {
        return $user->can('Delete:Siswa');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Siswa');
    }

    public function restore(User $user, Siswa $siswa): bool
    {
        return $user->can('Restore:Siswa');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Siswa');
    }

    public function forceDelete(User $user, Siswa $siswa): bool
    {
        return $user->can('ForceDelete:Siswa');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Siswa');
    }

    public function replicate(User $user, Siswa $siswa): bool
    {
        return $user->can('Replicate:Siswa');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:Siswa');
    }
}
