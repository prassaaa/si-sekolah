<?php

namespace App\Policies;

use App\Models\BuktiTransfer;
use App\Models\User;

class BuktiTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:BuktiTransfer');
    }

    public function view(User $user, BuktiTransfer $buktiTransfer): bool
    {
        return $user->can('View:BuktiTransfer');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:BuktiTransfer');
    }

    public function update(User $user, BuktiTransfer $buktiTransfer): bool
    {
        return $user->can('Update:BuktiTransfer');
    }

    public function delete(User $user, BuktiTransfer $buktiTransfer): bool
    {
        return $user->can('Delete:BuktiTransfer');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:BuktiTransfer');
    }

    public function restore(User $user, BuktiTransfer $buktiTransfer): bool
    {
        return $user->can('Restore:BuktiTransfer');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:BuktiTransfer');
    }

    public function forceDelete(User $user, BuktiTransfer $buktiTransfer): bool
    {
        return $user->can('ForceDelete:BuktiTransfer');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:BuktiTransfer');
    }

    public function replicate(User $user, BuktiTransfer $buktiTransfer): bool
    {
        return $user->can('Replicate:BuktiTransfer');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:BuktiTransfer');
    }
}
