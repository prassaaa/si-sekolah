<?php

namespace App\Policies;

use App\Models\KenaikanKelas;
use App\Models\User;

class KenaikanKelasPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:KenaikanKelas');
    }

    public function view(User $user, KenaikanKelas $kenaikanKelas): bool
    {
        return $user->can('View:KenaikanKelas');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:KenaikanKelas');
    }

    public function update(User $user, KenaikanKelas $kenaikanKelas): bool
    {
        return $user->can('Update:KenaikanKelas');
    }

    public function delete(User $user, KenaikanKelas $kenaikanKelas): bool
    {
        return $user->can('Delete:KenaikanKelas');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:KenaikanKelas');
    }

    public function restore(User $user, KenaikanKelas $kenaikanKelas): bool
    {
        return $user->can('Restore:KenaikanKelas');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:KenaikanKelas');
    }

    public function forceDelete(User $user, KenaikanKelas $kenaikanKelas): bool
    {
        return $user->can('ForceDelete:KenaikanKelas');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:KenaikanKelas');
    }

    public function replicate(User $user, KenaikanKelas $kenaikanKelas): bool
    {
        return $user->can('Replicate:KenaikanKelas');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:KenaikanKelas');
    }
}
