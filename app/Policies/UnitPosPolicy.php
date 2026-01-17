<?php

namespace App\Policies;

use App\Models\UnitPos;
use App\Models\User;

class UnitPosPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:UnitPos');
    }

    public function view(User $user, UnitPos $unitPos): bool
    {
        return $user->can('View:UnitPos');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:UnitPos');
    }

    public function update(User $user, UnitPos $unitPos): bool
    {
        return $user->can('Update:UnitPos');
    }

    public function delete(User $user, UnitPos $unitPos): bool
    {
        return $user->can('Delete:UnitPos');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:UnitPos');
    }

    public function restore(User $user, UnitPos $unitPos): bool
    {
        return $user->can('Restore:UnitPos');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:UnitPos');
    }

    public function forceDelete(User $user, UnitPos $unitPos): bool
    {
        return $user->can('ForceDelete:UnitPos');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:UnitPos');
    }

    public function replicate(User $user, UnitPos $unitPos): bool
    {
        return $user->can('Replicate:UnitPos');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:UnitPos');
    }
}
