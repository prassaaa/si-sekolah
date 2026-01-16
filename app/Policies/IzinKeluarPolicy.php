<?php

namespace App\Policies;

use App\Models\IzinKeluar;
use App\Models\User;

class IzinKeluarPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:IzinKeluar');
    }

    public function view(User $user, IzinKeluar $izinKeluar): bool
    {
        return $user->can('View:IzinKeluar');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:IzinKeluar');
    }

    public function update(User $user, IzinKeluar $izinKeluar): bool
    {
        return $user->can('Update:IzinKeluar');
    }

    public function delete(User $user, IzinKeluar $izinKeluar): bool
    {
        return $user->can('Delete:IzinKeluar');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:IzinKeluar');
    }

    public function restore(User $user, IzinKeluar $izinKeluar): bool
    {
        return $user->can('Restore:IzinKeluar');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:IzinKeluar');
    }

    public function forceDelete(User $user, IzinKeluar $izinKeluar): bool
    {
        return $user->can('ForceDelete:IzinKeluar');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:IzinKeluar');
    }

    public function replicate(User $user, IzinKeluar $izinKeluar): bool
    {
        return $user->can('Replicate:IzinKeluar');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:IzinKeluar');
    }
}
