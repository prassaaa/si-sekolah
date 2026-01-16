<?php

namespace App\Policies;

use App\Models\JenisPembayaran;
use App\Models\User;

class JenisPembayaranPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:JenisPembayaran');
    }

    public function view(User $user, JenisPembayaran $jenisPembayaran): bool
    {
        return $user->can('View:JenisPembayaran');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:JenisPembayaran');
    }

    public function update(User $user, JenisPembayaran $jenisPembayaran): bool
    {
        return $user->can('Update:JenisPembayaran');
    }

    public function delete(User $user, JenisPembayaran $jenisPembayaran): bool
    {
        return $user->can('Delete:JenisPembayaran');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:JenisPembayaran');
    }

    public function restore(User $user, JenisPembayaran $jenisPembayaran): bool
    {
        return $user->can('Restore:JenisPembayaran');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:JenisPembayaran');
    }

    public function forceDelete(User $user, JenisPembayaran $jenisPembayaran): bool
    {
        return $user->can('ForceDelete:JenisPembayaran');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:JenisPembayaran');
    }

    public function replicate(User $user, JenisPembayaran $jenisPembayaran): bool
    {
        return $user->can('Replicate:JenisPembayaran');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:JenisPembayaran');
    }
}
