<?php

namespace App\Policies;

use App\Models\Semester;
use App\Models\User;

class SemesterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Semester');
    }

    public function view(User $user, Semester $semester): bool
    {
        return $user->can('View:Semester');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Semester');
    }

    public function update(User $user, Semester $semester): bool
    {
        return $user->can('Update:Semester');
    }

    public function delete(User $user, Semester $semester): bool
    {
        return $user->can('Delete:Semester');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Semester');
    }

    public function restore(User $user, Semester $semester): bool
    {
        return $user->can('Restore:Semester');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Semester');
    }

    public function forceDelete(User $user, Semester $semester): bool
    {
        return $user->can('ForceDelete:Semester');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Semester');
    }

    public function replicate(User $user, Semester $semester): bool
    {
        return $user->can('Replicate:Semester');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:Semester');
    }
}
