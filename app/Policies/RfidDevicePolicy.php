<?php

namespace App\Policies;

use App\Models\RfidDevice;
use App\Models\User;

class RfidDevicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:RfidDevice');
    }

    public function view(User $user, RfidDevice $rfidDevice): bool
    {
        return $user->can('View:RfidDevice');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:RfidDevice');
    }

    public function update(User $user, RfidDevice $rfidDevice): bool
    {
        return $user->can('Update:RfidDevice');
    }

    public function delete(User $user, RfidDevice $rfidDevice): bool
    {
        return $user->can('Delete:RfidDevice');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:RfidDevice');
    }

    public function restore(User $user, RfidDevice $rfidDevice): bool
    {
        return $user->can('Restore:RfidDevice');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:RfidDevice');
    }

    public function forceDelete(User $user, RfidDevice $rfidDevice): bool
    {
        return $user->can('ForceDelete:RfidDevice');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:RfidDevice');
    }

    public function replicate(User $user, RfidDevice $rfidDevice): bool
    {
        return $user->can('Replicate:RfidDevice');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:RfidDevice');
    }
}
