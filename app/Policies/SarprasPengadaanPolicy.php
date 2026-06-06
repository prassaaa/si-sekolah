<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SarprasPengadaan;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SarprasPengadaanPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SarprasPengadaan');
    }

    public function view(AuthUser $authUser, SarprasPengadaan $sarprasPengadaan): bool
    {
        return $authUser->can('View:SarprasPengadaan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SarprasPengadaan');
    }

    public function update(AuthUser $authUser, SarprasPengadaan $sarprasPengadaan): bool
    {
        return $authUser->can('Update:SarprasPengadaan');
    }

    public function delete(AuthUser $authUser, SarprasPengadaan $sarprasPengadaan): bool
    {
        return $authUser->can('Delete:SarprasPengadaan');
    }

    public function restore(AuthUser $authUser, SarprasPengadaan $sarprasPengadaan): bool
    {
        return $authUser->can('Restore:SarprasPengadaan');
    }

    public function forceDelete(AuthUser $authUser, SarprasPengadaan $sarprasPengadaan): bool
    {
        return $authUser->can('ForceDelete:SarprasPengadaan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SarprasPengadaan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SarprasPengadaan');
    }

    public function replicate(AuthUser $authUser, SarprasPengadaan $sarprasPengadaan): bool
    {
        return $authUser->can('Replicate:SarprasPengadaan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SarprasPengadaan');
    }
}
