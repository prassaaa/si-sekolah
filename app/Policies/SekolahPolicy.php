<?php

namespace App\Policies;

use App\Models\Sekolah;
use App\Models\User;

class SekolahPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Sekolah');
    }

    public function view(User $user, Sekolah $sekolah): bool
    {
        return $user->can('View:Sekolah');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Sekolah');
    }

    public function update(User $user, Sekolah $sekolah): bool
    {
        return $user->can('Update:Sekolah');
    }

    public function delete(User $user, Sekolah $sekolah): bool
    {
        return $user->can('Delete:Sekolah');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Sekolah');
    }

    public function restore(User $user, Sekolah $sekolah): bool
    {
        return $user->can('Restore:Sekolah');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Sekolah');
    }

    public function forceDelete(User $user, Sekolah $sekolah): bool
    {
        return $user->can('ForceDelete:Sekolah');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Sekolah');
    }

    public function replicate(User $user, Sekolah $sekolah): bool
    {
        return $user->can('Replicate:Sekolah');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:Sekolah');
    }
}
