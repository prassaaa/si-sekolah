<?php

namespace App\Policies;

use App\Models\Pembayaran;
use App\Models\User;

class PembayaranPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Pembayaran');
    }

    public function view(User $user, Pembayaran $pembayaran): bool
    {
        return $user->can('View:Pembayaran');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Pembayaran');
    }

    public function update(User $user, Pembayaran $pembayaran): bool
    {
        return $user->can('Update:Pembayaran');
    }

    public function delete(User $user, Pembayaran $pembayaran): bool
    {
        return $user->can('Delete:Pembayaran');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Pembayaran');
    }

    public function restore(User $user, Pembayaran $pembayaran): bool
    {
        return $user->can('Restore:Pembayaran');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Pembayaran');
    }

    public function forceDelete(User $user, Pembayaran $pembayaran): bool
    {
        return $user->can('ForceDelete:Pembayaran');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Pembayaran');
    }

    public function replicate(User $user, Pembayaran $pembayaran): bool
    {
        return $user->can('Replicate:Pembayaran');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:Pembayaran');
    }
}
