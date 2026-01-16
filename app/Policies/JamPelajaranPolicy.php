<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\JamPelajaran;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class JamPelajaranPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:JamPelajaran');
    }

    public function view(AuthUser $authUser, JamPelajaran $jamPelajaran): bool
    {
        return $authUser->can('View:JamPelajaran');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:JamPelajaran');
    }

    public function update(AuthUser $authUser, JamPelajaran $jamPelajaran): bool
    {
        return $authUser->can('Update:JamPelajaran');
    }

    public function delete(AuthUser $authUser, JamPelajaran $jamPelajaran): bool
    {
        return $authUser->can('Delete:JamPelajaran');
    }

    public function restore(AuthUser $authUser, JamPelajaran $jamPelajaran): bool
    {
        return $authUser->can('Restore:JamPelajaran');
    }

    public function forceDelete(AuthUser $authUser, JamPelajaran $jamPelajaran): bool
    {
        return $authUser->can('ForceDelete:JamPelajaran');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:JamPelajaran');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:JamPelajaran');
    }

    public function replicate(AuthUser $authUser, JamPelajaran $jamPelajaran): bool
    {
        return $authUser->can('Replicate:JamPelajaran');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:JamPelajaran');
    }
}
