<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SarprasBarang;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SarprasBarangPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SarprasBarang');
    }

    public function view(AuthUser $authUser, SarprasBarang $sarprasBarang): bool
    {
        return $authUser->can('View:SarprasBarang');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SarprasBarang');
    }

    public function update(AuthUser $authUser, SarprasBarang $sarprasBarang): bool
    {
        return $authUser->can('Update:SarprasBarang');
    }

    public function delete(AuthUser $authUser, SarprasBarang $sarprasBarang): bool
    {
        return $authUser->can('Delete:SarprasBarang');
    }

    public function restore(AuthUser $authUser, SarprasBarang $sarprasBarang): bool
    {
        return $authUser->can('Restore:SarprasBarang');
    }

    public function forceDelete(AuthUser $authUser, SarprasBarang $sarprasBarang): bool
    {
        return $authUser->can('ForceDelete:SarprasBarang');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SarprasBarang');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SarprasBarang');
    }

    public function replicate(AuthUser $authUser, SarprasBarang $sarprasBarang): bool
    {
        return $authUser->can('Replicate:SarprasBarang');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SarprasBarang');
    }
}
