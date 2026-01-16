<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class JadwalPelajaranPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:JadwalPelajaran');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:JadwalPelajaran');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:JadwalPelajaran');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:JadwalPelajaran');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:JadwalPelajaran');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:JadwalPelajaran');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:JadwalPelajaran');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:JadwalPelajaran');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:JadwalPelajaran');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:JadwalPelajaran');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:JadwalPelajaran');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:JadwalPelajaran');
    }
}
