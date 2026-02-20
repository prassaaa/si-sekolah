<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Activity');
    }

    public function view(User $user, Activity $activity): bool
    {
        return $user->can('View:Activity');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Activity');
    }

    public function update(User $user, Activity $activity): bool
    {
        return $user->can('Update:Activity');
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $user->can('Delete:Activity');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Activity');
    }

    public function restore(User $user, Activity $activity): bool
    {
        return $user->can('Restore:Activity');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Activity');
    }

    public function forceDelete(User $user, Activity $activity): bool
    {
        return $user->can('ForceDelete:Activity');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Activity');
    }

    public function replicate(User $user, Activity $activity): bool
    {
        return $user->can('Replicate:Activity');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:Activity');
    }
}
