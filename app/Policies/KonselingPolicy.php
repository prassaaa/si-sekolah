<?php

namespace App\Policies;

use App\Models\Konseling;
use App\Models\User;

class KonselingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Konseling');
    }

    public function view(User $user, Konseling $konseling): bool
    {
        return $user->can('View:Konseling');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Konseling');
    }

    public function update(User $user, Konseling $konseling): bool
    {
        return $user->can('Update:Konseling');
    }

    public function delete(User $user, Konseling $konseling): bool
    {
        return $user->can('Delete:Konseling');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Konseling');
    }

    public function restore(User $user, Konseling $konseling): bool
    {
        return $user->can('Restore:Konseling');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Konseling');
    }

    public function forceDelete(User $user, Konseling $konseling): bool
    {
        return $user->can('ForceDelete:Konseling');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Konseling');
    }

    public function replicate(User $user, Konseling $konseling): bool
    {
        return $user->can('Replicate:Konseling');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:Konseling');
    }
}
