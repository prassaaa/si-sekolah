<?php

namespace App\Policies;

use App\Models\TahunAjaran;
use App\Models\User;

class TahunAjaranPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:TahunAjaran');
    }

    public function view(User $user, TahunAjaran $tahunAjaran): bool
    {
        return $user->can('View:TahunAjaran');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:TahunAjaran');
    }

    public function update(User $user, TahunAjaran $tahunAjaran): bool
    {
        return $user->can('Update:TahunAjaran');
    }

    public function delete(User $user, TahunAjaran $tahunAjaran): bool
    {
        return $user->can('Delete:TahunAjaran');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:TahunAjaran');
    }

    public function restore(User $user, TahunAjaran $tahunAjaran): bool
    {
        return $user->can('Restore:TahunAjaran');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:TahunAjaran');
    }

    public function forceDelete(User $user, TahunAjaran $tahunAjaran): bool
    {
        return $user->can('ForceDelete:TahunAjaran');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:TahunAjaran');
    }

    public function replicate(User $user, TahunAjaran $tahunAjaran): bool
    {
        return $user->can('Replicate:TahunAjaran');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:TahunAjaran');
    }
}
