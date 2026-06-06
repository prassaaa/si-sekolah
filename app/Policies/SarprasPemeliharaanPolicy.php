<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SarprasPemeliharaan;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SarprasPemeliharaanPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SarprasPemeliharaan');
    }

    public function view(AuthUser $authUser, SarprasPemeliharaan $sarprasPemeliharaan): bool
    {
        return $authUser->can('View:SarprasPemeliharaan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SarprasPemeliharaan');
    }

    public function update(AuthUser $authUser, SarprasPemeliharaan $sarprasPemeliharaan): bool
    {
        return $authUser->can('Update:SarprasPemeliharaan');
    }

    public function delete(AuthUser $authUser, SarprasPemeliharaan $sarprasPemeliharaan): bool
    {
        return $authUser->can('Delete:SarprasPemeliharaan');
    }

    public function restore(AuthUser $authUser, SarprasPemeliharaan $sarprasPemeliharaan): bool
    {
        return $authUser->can('Restore:SarprasPemeliharaan');
    }

    public function forceDelete(AuthUser $authUser, SarprasPemeliharaan $sarprasPemeliharaan): bool
    {
        return $authUser->can('ForceDelete:SarprasPemeliharaan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SarprasPemeliharaan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SarprasPemeliharaan');
    }

    public function replicate(AuthUser $authUser, SarprasPemeliharaan $sarprasPemeliharaan): bool
    {
        return $authUser->can('Replicate:SarprasPemeliharaan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SarprasPemeliharaan');
    }
}
