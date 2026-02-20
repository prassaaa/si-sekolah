<?php

namespace App\Policies;

use App\Models\JabatanPegawai;
use App\Models\User;

class JabatanPegawaiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:JabatanPegawai');
    }

    public function view(User $user, JabatanPegawai $jabatanPegawai): bool
    {
        return $user->can('View:JabatanPegawai');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:JabatanPegawai');
    }

    public function update(User $user, JabatanPegawai $jabatanPegawai): bool
    {
        return $user->can('Update:JabatanPegawai');
    }

    public function delete(User $user, JabatanPegawai $jabatanPegawai): bool
    {
        return $user->can('Delete:JabatanPegawai');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:JabatanPegawai');
    }

    public function restore(User $user, JabatanPegawai $jabatanPegawai): bool
    {
        return $user->can('Restore:JabatanPegawai');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:JabatanPegawai');
    }

    public function forceDelete(
        User $user,
        JabatanPegawai $jabatanPegawai,
    ): bool {
        return $user->can('ForceDelete:JabatanPegawai');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:JabatanPegawai');
    }

    public function replicate(User $user, JabatanPegawai $jabatanPegawai): bool
    {
        return $user->can('Replicate:JabatanPegawai');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:JabatanPegawai');
    }
}
