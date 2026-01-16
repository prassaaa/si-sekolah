<?php

namespace App\Policies;

use App\Models\Kelulusan;
use App\Models\User;

class KelulusanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Kelulusan');
    }

    public function view(User $user, Kelulusan $kelulusan): bool
    {
        return $user->can('View:Kelulusan');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Kelulusan');
    }

    public function update(User $user, Kelulusan $kelulusan): bool
    {
        return $user->can('Update:Kelulusan');
    }

    public function delete(User $user, Kelulusan $kelulusan): bool
    {
        return $user->can('Delete:Kelulusan');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Kelulusan');
    }

    public function restore(User $user, Kelulusan $kelulusan): bool
    {
        return $user->can('Restore:Kelulusan');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Kelulusan');
    }

    public function forceDelete(User $user, Kelulusan $kelulusan): bool
    {
        return $user->can('ForceDelete:Kelulusan');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Kelulusan');
    }

    public function replicate(User $user, Kelulusan $kelulusan): bool
    {
        return $user->can('Replicate:Kelulusan');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:Kelulusan');
    }
}
