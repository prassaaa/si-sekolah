<?php

namespace App\Policies;

use App\Models\Tahfidz;
use App\Models\User;

class TahfidzPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Tahfidz');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Tahfidz $tahfidz): bool
    {
        return $user->can('View:Tahfidz');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('Create:Tahfidz');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Tahfidz $tahfidz): bool
    {
        return $user->can('Update:Tahfidz');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Tahfidz $tahfidz): bool
    {
        return $user->can('Delete:Tahfidz');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Tahfidz');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Tahfidz $tahfidz): bool
    {
        return $user->can('Restore:Tahfidz');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Tahfidz');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Tahfidz $tahfidz): bool
    {
        return $user->can('ForceDelete:Tahfidz');
    }

    /**
     * Determine whether the user can bulk permanently delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Tahfidz');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, Tahfidz $tahfidz): bool
    {
        return $user->can('Replicate:Tahfidz');
    }

    /**
     * Determine whether the user can reorder models.
     */
    public function reorder(User $user): bool
    {
        return $user->can('Reorder:Tahfidz');
    }
}
