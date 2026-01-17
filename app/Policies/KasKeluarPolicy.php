<?php

namespace App\Policies;

use App\Models\KasKeluar;
use App\Models\User;

class KasKeluarPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:KasKeluar');
    }

    public function view(User $user, KasKeluar $kasKeluar): bool
    {
        return $user->can('View:KasKeluar');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:KasKeluar');
    }

    public function update(User $user, KasKeluar $kasKeluar): bool
    {
        return $user->can('Update:KasKeluar');
    }

    public function delete(User $user, KasKeluar $kasKeluar): bool
    {
        return $user->can('Delete:KasKeluar');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:KasKeluar');
    }

    public function restore(User $user, KasKeluar $kasKeluar): bool
    {
        return $user->can('Restore:KasKeluar');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:KasKeluar');
    }

    public function forceDelete(User $user, KasKeluar $kasKeluar): bool
    {
        return $user->can('ForceDelete:KasKeluar');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:KasKeluar');
    }

    public function replicate(User $user, KasKeluar $kasKeluar): bool
    {
        return $user->can('Replicate:KasKeluar');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:KasKeluar');
    }
}
