<?php

namespace App\Policies;

use App\Models\Akun;
use App\Models\User;

class AkunPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Akun');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Akun $akun): bool
    {
        return $user->can('View:Akun');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('Create:Akun');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Akun $akun): bool
    {
        return $user->can('Update:Akun');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Akun $akun): bool
    {
        return $user->can('Delete:Akun');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Akun');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Akun $akun): bool
    {
        return $user->can('Restore:Akun');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Akun');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Akun $akun): bool
    {
        return $user->can('ForceDelete:Akun');
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Akun');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, Akun $akun): bool
    {
        return $user->can('Replicate:Akun');
    }

    /**
     * Determine whether the user can reorder models.
     */
    public function reorder(User $user): bool
    {
        return $user->can('Reorder:Akun');
    }
}
