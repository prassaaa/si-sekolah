<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Sekolah;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SekolahPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Sekolah');
    }

    public function view(AuthUser $authUser, Sekolah $sekolah): bool
    {
        return $authUser->can('View:Sekolah');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Sekolah');
    }

    public function update(AuthUser $authUser, Sekolah $sekolah): bool
    {
        return $authUser->can('Update:Sekolah');
    }

    public function delete(AuthUser $authUser, Sekolah $sekolah): bool
    {
        return $authUser->can('Delete:Sekolah');
    }

    public function restore(AuthUser $authUser, Sekolah $sekolah): bool
    {
        return $authUser->can('Restore:Sekolah');
    }

    public function forceDelete(AuthUser $authUser, Sekolah $sekolah): bool
    {
        return $authUser->can('ForceDelete:Sekolah');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Sekolah');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Sekolah');
    }

    public function replicate(AuthUser $authUser, Sekolah $sekolah): bool
    {
        return $authUser->can('Replicate:Sekolah');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Sekolah');
    }
}
