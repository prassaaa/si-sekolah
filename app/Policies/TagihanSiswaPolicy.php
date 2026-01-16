<?php

namespace App\Policies;

use App\Models\TagihanSiswa;
use App\Models\User;

class TagihanSiswaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:TagihanSiswa');
    }

    public function view(User $user, TagihanSiswa $tagihanSiswa): bool
    {
        return $user->can('View:TagihanSiswa');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:TagihanSiswa');
    }

    public function update(User $user, TagihanSiswa $tagihanSiswa): bool
    {
        return $user->can('Update:TagihanSiswa');
    }

    public function delete(User $user, TagihanSiswa $tagihanSiswa): bool
    {
        return $user->can('Delete:TagihanSiswa');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:TagihanSiswa');
    }

    public function restore(User $user, TagihanSiswa $tagihanSiswa): bool
    {
        return $user->can('Restore:TagihanSiswa');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:TagihanSiswa');
    }

    public function forceDelete(User $user, TagihanSiswa $tagihanSiswa): bool
    {
        return $user->can('ForceDelete:TagihanSiswa');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:TagihanSiswa');
    }

    public function replicate(User $user, TagihanSiswa $tagihanSiswa): bool
    {
        return $user->can('Replicate:TagihanSiswa');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:TagihanSiswa');
    }
}
