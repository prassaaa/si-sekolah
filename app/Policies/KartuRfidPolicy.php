<?php

namespace App\Policies;

use App\Models\KartuRfid;
use App\Models\User;

class KartuRfidPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:KartuRfid');
    }

    public function view(User $user, KartuRfid $kartuRfid): bool
    {
        return $user->can('View:KartuRfid');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:KartuRfid');
    }

    public function update(User $user, KartuRfid $kartuRfid): bool
    {
        return $user->can('Update:KartuRfid');
    }

    public function delete(User $user, KartuRfid $kartuRfid): bool
    {
        return $user->can('Delete:KartuRfid');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:KartuRfid');
    }

    public function restore(User $user, KartuRfid $kartuRfid): bool
    {
        return $user->can('Restore:KartuRfid');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:KartuRfid');
    }

    public function forceDelete(User $user, KartuRfid $kartuRfid): bool
    {
        return $user->can('ForceDelete:KartuRfid');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:KartuRfid');
    }

    public function replicate(User $user, KartuRfid $kartuRfid): bool
    {
        return $user->can('Replicate:KartuRfid');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:KartuRfid');
    }
}
