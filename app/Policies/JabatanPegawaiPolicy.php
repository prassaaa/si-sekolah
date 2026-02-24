<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\JabatanPegawai;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class JabatanPegawaiPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:JabatanPegawai');
    }

    public function view(AuthUser $authUser, JabatanPegawai $jabatanPegawai): bool
    {
        return $authUser->can('View:JabatanPegawai');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:JabatanPegawai');
    }

    public function update(AuthUser $authUser, JabatanPegawai $jabatanPegawai): bool
    {
        return $authUser->can('Update:JabatanPegawai');
    }

    public function delete(AuthUser $authUser, JabatanPegawai $jabatanPegawai): bool
    {
        return $authUser->can('Delete:JabatanPegawai');
    }

    public function restore(AuthUser $authUser, JabatanPegawai $jabatanPegawai): bool
    {
        return $authUser->can('Restore:JabatanPegawai');
    }

    public function forceDelete(AuthUser $authUser, JabatanPegawai $jabatanPegawai): bool
    {
        return $authUser->can('ForceDelete:JabatanPegawai');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:JabatanPegawai');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:JabatanPegawai');
    }

    public function replicate(AuthUser $authUser, JabatanPegawai $jabatanPegawai): bool
    {
        return $authUser->can('Replicate:JabatanPegawai');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:JabatanPegawai');
    }
}
