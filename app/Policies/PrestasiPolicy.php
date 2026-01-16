<?php

namespace App\Policies;

use App\Models\Prestasi;
use App\Models\User;

class PrestasiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Prestasi');
    }

    public function view(User $user, Prestasi $prestasi): bool
    {
        return $user->can('View:Prestasi');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Prestasi');
    }

    public function update(User $user, Prestasi $prestasi): bool
    {
        return $user->can('Update:Prestasi');
    }

    public function delete(User $user, Prestasi $prestasi): bool
    {
        return $user->can('Delete:Prestasi');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Prestasi');
    }

    public function restore(User $user, Prestasi $prestasi): bool
    {
        return $user->can('Restore:Prestasi');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Prestasi');
    }

    public function forceDelete(User $user, Prestasi $prestasi): bool
    {
        return $user->can('ForceDelete:Prestasi');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Prestasi');
    }

    public function replicate(User $user, Prestasi $prestasi): bool
    {
        return $user->can('Replicate:Prestasi');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:Prestasi');
    }
}
