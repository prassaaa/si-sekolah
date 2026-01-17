<?php

namespace App\Policies;

use App\Models\TabunganSiswa;
use App\Models\User;

class TabunganSiswaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:TabunganSiswa');
    }

    public function view(User $user, TabunganSiswa $tabunganSiswa): bool
    {
        return $user->can('View:TabunganSiswa');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:TabunganSiswa');
    }

    public function update(User $user, TabunganSiswa $tabunganSiswa): bool
    {
        return $user->can('Update:TabunganSiswa');
    }

    public function delete(User $user, TabunganSiswa $tabunganSiswa): bool
    {
        return $user->can('Delete:TabunganSiswa');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:TabunganSiswa');
    }

    public function restore(User $user, TabunganSiswa $tabunganSiswa): bool
    {
        return $user->can('Restore:TabunganSiswa');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:TabunganSiswa');
    }

    public function forceDelete(User $user, TabunganSiswa $tabunganSiswa): bool
    {
        return $user->can('ForceDelete:TabunganSiswa');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:TabunganSiswa');
    }

    public function replicate(User $user, TabunganSiswa $tabunganSiswa): bool
    {
        return $user->can('Replicate:TabunganSiswa');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:TabunganSiswa');
    }
}
