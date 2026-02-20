<?php

namespace App\Policies;

use App\Models\Informasi;
use App\Models\User;

class InformasiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Informasi');
    }

    public function view(User $user, Informasi $informasi): bool
    {
        return $user->can('View:Informasi');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Informasi');
    }

    public function update(User $user, Informasi $informasi): bool
    {
        return $user->can('Update:Informasi');
    }

    public function delete(User $user, Informasi $informasi): bool
    {
        return $user->can('Delete:Informasi');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Informasi');
    }

    public function restore(User $user, Informasi $informasi): bool
    {
        return $user->can('Restore:Informasi');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Informasi');
    }

    public function forceDelete(User $user, Informasi $informasi): bool
    {
        return $user->can('ForceDelete:Informasi');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Informasi');
    }

    public function replicate(User $user, Informasi $informasi): bool
    {
        return $user->can('Replicate:Informasi');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:Informasi');
    }
}
