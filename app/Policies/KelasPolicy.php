<?php

namespace App\Policies;

use App\Models\Kelas;
use App\Models\User;

class KelasPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Kelas');
    }

    public function view(User $user, Kelas $kelas): bool
    {
        return $user->can('View:Kelas');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Kelas');
    }

    public function update(User $user, Kelas $kelas): bool
    {
        return $user->can('Update:Kelas');
    }

    public function delete(User $user, Kelas $kelas): bool
    {
        return $user->can('Delete:Kelas');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Kelas');
    }

    public function restore(User $user, Kelas $kelas): bool
    {
        return $user->can('Restore:Kelas');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Kelas');
    }

    public function forceDelete(User $user, Kelas $kelas): bool
    {
        return $user->can('ForceDelete:Kelas');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Kelas');
    }

    public function replicate(User $user, Kelas $kelas): bool
    {
        return $user->can('Replicate:Kelas');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:Kelas');
    }
}
