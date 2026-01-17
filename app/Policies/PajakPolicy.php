<?php

namespace App\Policies;

use App\Models\Pajak;
use App\Models\User;

class PajakPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Pajak');
    }

    public function view(User $user, Pajak $pajak): bool
    {
        return $user->can('View:Pajak');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Pajak');
    }

    public function update(User $user, Pajak $pajak): bool
    {
        return $user->can('Update:Pajak');
    }

    public function delete(User $user, Pajak $pajak): bool
    {
        return $user->can('Delete:Pajak');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Pajak');
    }

    public function restore(User $user, Pajak $pajak): bool
    {
        return $user->can('Restore:Pajak');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Pajak');
    }

    public function forceDelete(User $user, Pajak $pajak): bool
    {
        return $user->can('ForceDelete:Pajak');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Pajak');
    }

    public function replicate(User $user, Pajak $pajak): bool
    {
        return $user->can('Replicate:Pajak');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:Pajak');
    }
}
