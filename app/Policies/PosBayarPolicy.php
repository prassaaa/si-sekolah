<?php

namespace App\Policies;

use App\Models\PosBayar;
use App\Models\User;

class PosBayarPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:PosBayar');
    }

    public function view(User $user, PosBayar $posBayar): bool
    {
        return $user->can('View:PosBayar');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:PosBayar');
    }

    public function update(User $user, PosBayar $posBayar): bool
    {
        return $user->can('Update:PosBayar');
    }

    public function delete(User $user, PosBayar $posBayar): bool
    {
        return $user->can('Delete:PosBayar');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:PosBayar');
    }

    public function restore(User $user, PosBayar $posBayar): bool
    {
        return $user->can('Restore:PosBayar');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:PosBayar');
    }

    public function forceDelete(User $user, PosBayar $posBayar): bool
    {
        return $user->can('ForceDelete:PosBayar');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:PosBayar');
    }

    public function replicate(User $user, PosBayar $posBayar): bool
    {
        return $user->can('Replicate:PosBayar');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:PosBayar');
    }
}
