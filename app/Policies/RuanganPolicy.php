<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Ruangan;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RuanganPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Ruangan');
    }

    public function view(AuthUser $authUser, Ruangan $ruangan): bool
    {
        return $authUser->can('View:Ruangan');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Ruangan');
    }

    public function update(AuthUser $authUser, Ruangan $ruangan): bool
    {
        return $authUser->can('Update:Ruangan');
    }

    public function delete(AuthUser $authUser, Ruangan $ruangan): bool
    {
        return $authUser->can('Delete:Ruangan');
    }

    public function restore(AuthUser $authUser, Ruangan $ruangan): bool
    {
        return $authUser->can('Restore:Ruangan');
    }

    public function forceDelete(AuthUser $authUser, Ruangan $ruangan): bool
    {
        return $authUser->can('ForceDelete:Ruangan');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Ruangan');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Ruangan');
    }

    public function replicate(AuthUser $authUser, Ruangan $ruangan): bool
    {
        return $authUser->can('Replicate:Ruangan');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Ruangan');
    }
}
