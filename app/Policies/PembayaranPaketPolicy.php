<?php

namespace App\Policies;

use App\Models\PembayaranPaket;
use App\Models\User;

class PembayaranPaketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:PembayaranPaket');
    }

    public function view(User $user, PembayaranPaket $pembayaranPaket): bool
    {
        return $user->can('View:PembayaranPaket');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:PembayaranPaket');
    }

    public function update(User $user, PembayaranPaket $pembayaranPaket): bool
    {
        return $user->can('Update:PembayaranPaket');
    }

    public function delete(User $user, PembayaranPaket $pembayaranPaket): bool
    {
        return $user->can('Delete:PembayaranPaket');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:PembayaranPaket');
    }

    public function restore(User $user, PembayaranPaket $pembayaranPaket): bool
    {
        return $user->can('Restore:PembayaranPaket');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:PembayaranPaket');
    }

    public function forceDelete(User $user, PembayaranPaket $pembayaranPaket): bool
    {
        return $user->can('ForceDelete:PembayaranPaket');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:PembayaranPaket');
    }

    public function replicate(User $user, PembayaranPaket $pembayaranPaket): bool
    {
        return $user->can('Replicate:PembayaranPaket');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:PembayaranPaket');
    }
}
