<?php

namespace App\Policies;

use App\Models\JadwalPelajaran;
use App\Models\User;

class JadwalPelajaranPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:JadwalPelajaran');
    }

    public function view(User $user, JadwalPelajaran $jadwalPelajaran): bool
    {
        return $user->can('View:JadwalPelajaran');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:JadwalPelajaran');
    }

    public function update(User $user, JadwalPelajaran $jadwalPelajaran): bool
    {
        return $user->can('Update:JadwalPelajaran');
    }

    public function delete(User $user, JadwalPelajaran $jadwalPelajaran): bool
    {
        return $user->can('Delete:JadwalPelajaran');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:JadwalPelajaran');
    }

    public function restore(User $user, JadwalPelajaran $jadwalPelajaran): bool
    {
        return $user->can('Restore:JadwalPelajaran');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:JadwalPelajaran');
    }

    public function forceDelete(
        User $user,
        JadwalPelajaran $jadwalPelajaran,
    ): bool {
        return $user->can('ForceDelete:JadwalPelajaran');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:JadwalPelajaran');
    }

    public function replicate(
        User $user,
        JadwalPelajaran $jadwalPelajaran,
    ): bool {
        return $user->can('Replicate:JadwalPelajaran');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:JadwalPelajaran');
    }
}
