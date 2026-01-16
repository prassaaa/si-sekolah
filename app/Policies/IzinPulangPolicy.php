<?php

namespace App\Policies;

use App\Models\IzinPulang;
use App\Models\User;

class IzinPulangPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:IzinPulang');
    }

    public function view(User $user, IzinPulang $izinPulang): bool
    {
        return $user->can('View:IzinPulang');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:IzinPulang');
    }

    public function update(User $user, IzinPulang $izinPulang): bool
    {
        return $user->can('Update:IzinPulang');
    }

    public function delete(User $user, IzinPulang $izinPulang): bool
    {
        return $user->can('Delete:IzinPulang');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:IzinPulang');
    }

    public function restore(User $user, IzinPulang $izinPulang): bool
    {
        return $user->can('Restore:IzinPulang');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:IzinPulang');
    }

    public function forceDelete(User $user, IzinPulang $izinPulang): bool
    {
        return $user->can('ForceDelete:IzinPulang');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:IzinPulang');
    }

    public function replicate(User $user, IzinPulang $izinPulang): bool
    {
        return $user->can('Replicate:IzinPulang');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:IzinPulang');
    }
}
