<?php

namespace App\Policies;

use App\Models\SlipGaji;
use App\Models\User;

class SlipGajiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:SlipGaji');
    }

    /**
     * Izinkan lihat slip apabila user memiliki View:SlipGaji DAN:
     *   - memiliki Create:SlipGaji (pengelola payroll: bendahara / super_admin), ATAU
     *   - slip tersebut milik pegawai yang terhubung ke akun user ini (self-view guru).
     */
    public function view(User $user, SlipGaji $slipGaji): bool
    {
        if (! $user->can('View:SlipGaji')) {
            return false;
        }

        if ($user->can('Create:SlipGaji')) {
            return true;
        }

        return $slipGaji->pegawai?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('Create:SlipGaji');
    }

    public function update(User $user, SlipGaji $slipGaji): bool
    {
        return $user->can('Update:SlipGaji');
    }

    public function delete(User $user, SlipGaji $slipGaji): bool
    {
        return $user->can('Delete:SlipGaji');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:SlipGaji');
    }

    public function restore(User $user, SlipGaji $slipGaji): bool
    {
        return $user->can('Restore:SlipGaji');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:SlipGaji');
    }

    public function forceDelete(User $user, SlipGaji $slipGaji): bool
    {
        return $user->can('ForceDelete:SlipGaji');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:SlipGaji');
    }

    public function replicate(User $user, SlipGaji $slipGaji): bool
    {
        return $user->can('Replicate:SlipGaji');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:SlipGaji');
    }
}
