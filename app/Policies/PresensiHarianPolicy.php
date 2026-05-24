<?php

namespace App\Policies;

use App\Models\PresensiHarian;
use App\Models\User;

class PresensiHarianPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:PresensiHarian');
    }

    public function view(User $user, PresensiHarian $presensiHarian): bool
    {
        return $user->can('View:PresensiHarian');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:PresensiHarian');
    }

    public function update(User $user, PresensiHarian $presensiHarian): bool
    {
        return $user->can('Update:PresensiHarian');
    }

    public function delete(User $user, PresensiHarian $presensiHarian): bool
    {
        return $user->can('Delete:PresensiHarian');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:PresensiHarian');
    }

    public function restore(User $user, PresensiHarian $presensiHarian): bool
    {
        return $user->can('Restore:PresensiHarian');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:PresensiHarian');
    }

    public function forceDelete(User $user, PresensiHarian $presensiHarian): bool
    {
        return $user->can('ForceDelete:PresensiHarian');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:PresensiHarian');
    }

    public function replicate(User $user, PresensiHarian $presensiHarian): bool
    {
        return $user->can('Replicate:PresensiHarian');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:PresensiHarian');
    }
}
