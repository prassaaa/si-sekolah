<?php

namespace App\Policies;

use App\Models\MataPelajaran;
use App\Models\User;

class MataPelajaranPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:MataPelajaran');
    }

    public function view(User $user, MataPelajaran $mataPelajaran): bool
    {
        return $user->can('View:MataPelajaran');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:MataPelajaran');
    }

    public function update(User $user, MataPelajaran $mataPelajaran): bool
    {
        return $user->can('Update:MataPelajaran');
    }

    public function delete(User $user, MataPelajaran $mataPelajaran): bool
    {
        return $user->can('Delete:MataPelajaran');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:MataPelajaran');
    }

    public function restore(User $user, MataPelajaran $mataPelajaran): bool
    {
        return $user->can('Restore:MataPelajaran');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:MataPelajaran');
    }

    public function forceDelete(User $user, MataPelajaran $mataPelajaran): bool
    {
        return $user->can('ForceDelete:MataPelajaran');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:MataPelajaran');
    }

    public function replicate(User $user, MataPelajaran $mataPelajaran): bool
    {
        return $user->can('Replicate:MataPelajaran');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:MataPelajaran');
    }
}
