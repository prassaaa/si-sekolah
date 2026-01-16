<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TahunAjaran;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class TahunAjaranPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TahunAjaran');
    }

    public function view(AuthUser $authUser, TahunAjaran $tahunAjaran): bool
    {
        return $authUser->can('View:TahunAjaran');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TahunAjaran');
    }

    public function update(AuthUser $authUser, TahunAjaran $tahunAjaran): bool
    {
        return $authUser->can('Update:TahunAjaran');
    }

    public function delete(AuthUser $authUser, TahunAjaran $tahunAjaran): bool
    {
        return $authUser->can('Delete:TahunAjaran');
    }

    public function restore(AuthUser $authUser, TahunAjaran $tahunAjaran): bool
    {
        return $authUser->can('Restore:TahunAjaran');
    }

    public function forceDelete(AuthUser $authUser, TahunAjaran $tahunAjaran): bool
    {
        return $authUser->can('ForceDelete:TahunAjaran');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TahunAjaran');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TahunAjaran');
    }

    public function replicate(AuthUser $authUser, TahunAjaran $tahunAjaran): bool
    {
        return $authUser->can('Replicate:TahunAjaran');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TahunAjaran');
    }
}
