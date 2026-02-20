<?php

namespace App\Policies;

use App\Models\Pegawai;
use App\Models\User;

class PegawaiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Pegawai');
    }

    public function view(User $user, Pegawai $pegawai): bool
    {
        return $user->can('View:Pegawai');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Pegawai');
    }

    public function update(User $user, Pegawai $pegawai): bool
    {
        return $user->can('Update:Pegawai');
    }

    public function delete(User $user, Pegawai $pegawai): bool
    {
        return $user->can('Delete:Pegawai');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Pegawai');
    }

    public function restore(User $user, Pegawai $pegawai): bool
    {
        return $user->can('Restore:Pegawai');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Pegawai');
    }

    public function forceDelete(User $user, Pegawai $pegawai): bool
    {
        return $user->can('ForceDelete:Pegawai');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Pegawai');
    }

    public function replicate(User $user, Pegawai $pegawai): bool
    {
        return $user->can('Replicate:Pegawai');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:Pegawai');
    }
}
