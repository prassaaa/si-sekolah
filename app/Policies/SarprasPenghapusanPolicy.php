<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SarprasPenghapusan;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SarprasPenghapusanPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SarprasPenghapusan');
    }

    public function view(AuthUser $authUser, SarprasPenghapusan $sarprasPenghapusan): bool
    {
        return $authUser->can('View:SarprasPenghapusan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SarprasPenghapusan');
    }

    public function update(AuthUser $authUser, SarprasPenghapusan $sarprasPenghapusan): bool
    {
        return $authUser->can('Update:SarprasPenghapusan');
    }

    public function delete(AuthUser $authUser, SarprasPenghapusan $sarprasPenghapusan): bool
    {
        return $authUser->can('Delete:SarprasPenghapusan');
    }

    public function restore(AuthUser $authUser, SarprasPenghapusan $sarprasPenghapusan): bool
    {
        return $authUser->can('Restore:SarprasPenghapusan');
    }

    public function forceDelete(AuthUser $authUser, SarprasPenghapusan $sarprasPenghapusan): bool
    {
        return $authUser->can('ForceDelete:SarprasPenghapusan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SarprasPenghapusan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SarprasPenghapusan');
    }

    public function replicate(AuthUser $authUser, SarprasPenghapusan $sarprasPenghapusan): bool
    {
        return $authUser->can('Replicate:SarprasPenghapusan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SarprasPenghapusan');
    }
}
