<?php

namespace App\Policies;

use App\Models\Pelanggaran;
use App\Models\User;

class PelanggaranPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Pelanggaran');
    }

    public function view(User $user, Pelanggaran $pelanggaran): bool
    {
        return $user->can('View:Pelanggaran');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Pelanggaran');
    }

    public function update(User $user, Pelanggaran $pelanggaran): bool
    {
        return $user->can('Update:Pelanggaran');
    }

    public function delete(User $user, Pelanggaran $pelanggaran): bool
    {
        return $user->can('Delete:Pelanggaran');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Pelanggaran');
    }

    public function restore(User $user, Pelanggaran $pelanggaran): bool
    {
        return $user->can('Restore:Pelanggaran');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Pelanggaran');
    }

    public function forceDelete(User $user, Pelanggaran $pelanggaran): bool
    {
        return $user->can('ForceDelete:Pelanggaran');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Pelanggaran');
    }

    public function replicate(User $user, Pelanggaran $pelanggaran): bool
    {
        return $user->can('Replicate:Pelanggaran');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:Pelanggaran');
    }
}
