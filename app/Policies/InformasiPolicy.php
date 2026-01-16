<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Informasi;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class InformasiPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Informasi');
    }

    public function view(AuthUser $authUser, Informasi $informasi): bool
    {
        return $authUser->can('View:Informasi');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Informasi');
    }

    public function update(AuthUser $authUser, Informasi $informasi): bool
    {
        return $authUser->can('Update:Informasi');
    }

    public function delete(AuthUser $authUser, Informasi $informasi): bool
    {
        return $authUser->can('Delete:Informasi');
    }

    public function restore(AuthUser $authUser, Informasi $informasi): bool
    {
        return $authUser->can('Restore:Informasi');
    }

    public function forceDelete(AuthUser $authUser, Informasi $informasi): bool
    {
        return $authUser->can('ForceDelete:Informasi');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Informasi');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Informasi');
    }

    public function replicate(AuthUser $authUser, Informasi $informasi): bool
    {
        return $authUser->can('Replicate:Informasi');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Informasi');
    }
}
