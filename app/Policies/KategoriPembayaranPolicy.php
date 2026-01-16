<?php

namespace App\Policies;

use App\Models\KategoriPembayaran;
use App\Models\User;

class KategoriPembayaranPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:KategoriPembayaran');
    }

    public function view(User $user, KategoriPembayaran $kategoriPembayaran): bool
    {
        return $user->can('View:KategoriPembayaran');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:KategoriPembayaran');
    }

    public function update(User $user, KategoriPembayaran $kategoriPembayaran): bool
    {
        return $user->can('Update:KategoriPembayaran');
    }

    public function delete(User $user, KategoriPembayaran $kategoriPembayaran): bool
    {
        return $user->can('Delete:KategoriPembayaran');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:KategoriPembayaran');
    }

    public function restore(User $user, KategoriPembayaran $kategoriPembayaran): bool
    {
        return $user->can('Restore:KategoriPembayaran');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:KategoriPembayaran');
    }

    public function forceDelete(User $user, KategoriPembayaran $kategoriPembayaran): bool
    {
        return $user->can('ForceDelete:KategoriPembayaran');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:KategoriPembayaran');
    }

    public function replicate(User $user, KategoriPembayaran $kategoriPembayaran): bool
    {
        return $user->can('Replicate:KategoriPembayaran');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:KategoriPembayaran');
    }
}
