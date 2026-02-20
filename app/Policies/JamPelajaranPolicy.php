<?php

namespace App\Policies;

use App\Models\JamPelajaran;
use App\Models\User;

class JamPelajaranPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:JamPelajaran');
    }

    public function view(User $user, JamPelajaran $jamPelajaran): bool
    {
        return $user->can('View:JamPelajaran');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:JamPelajaran');
    }

    public function update(User $user, JamPelajaran $jamPelajaran): bool
    {
        return $user->can('Update:JamPelajaran');
    }

    public function delete(User $user, JamPelajaran $jamPelajaran): bool
    {
        return $user->can('Delete:JamPelajaran');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:JamPelajaran');
    }

    public function restore(User $user, JamPelajaran $jamPelajaran): bool
    {
        return $user->can('Restore:JamPelajaran');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:JamPelajaran');
    }

    public function forceDelete(User $user, JamPelajaran $jamPelajaran): bool
    {
        return $user->can('ForceDelete:JamPelajaran');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:JamPelajaran');
    }

    public function replicate(User $user, JamPelajaran $jamPelajaran): bool
    {
        return $user->can('Replicate:JamPelajaran');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:JamPelajaran');
    }
}
